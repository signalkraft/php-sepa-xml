<?php
/**
 * Created by JetBrains PhpStorm.
 * User: srohweder
 * Date: 1/8/14
 * Time: 10:28 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Tests\Unit;


use Digitick\Sepa\Exception\InvalidArgumentException;
use Digitick\Sepa\PaymentInformation;
use Digitick\Sepa\TransferFile\Factory\TransferFileFacadeFactory;
use Digitick\Sepa\TransferInformation\CustomerDirectDebitTransferInformation;

class SumOutputTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \DOMXPath
     */
    protected $directDebitXpath;

    protected function createDirectDebitXpathObject($amount)
    {
        $directDebit = TransferFileFacadeFactory::createDirectDebit('test123', 'Me');

        // create a payment, it's possible to create multiple payments,
        // "firstPayment" is the identifier for the transactions
        $directDebit->addPaymentInfo(
            'firstPayment',
            array(
                'id' => 'firstPayment',
                'creditorName' => 'My Company',
                'creditorAccountIBAN' => 'FI1350001540000056',
                'creditorAgentBIC' => 'PSSTFRPPMON',
                'seqType' => PaymentInformation::S_ONEOFF,
                'creditorId' => 'DE21WVM1234567890'
            )
        );
        // Add a Single Transaction to the named payment
        $directDebit->addTransfer(
            'firstPayment',
            array(
                'amount' => $amount,
                'debtorIban' => 'FI1350001540000056',
                'debtorBic' => 'OKOYFIHH',
                'debtorName' => 'Their Company',
                'debtorMandate' => 'AB12345',
                'debtorMandateSignDate' => '13.10.2012',
                'remittanceInformation' => 'Purpose of this direct debit'
            )
        );
        // Retrieve the resulting XML
        $xml = $directDebit->asXML();
        $doc = new \DOMDocument();
        $doc->loadXML($xml);
        $this->directDebitXpath = new \DOMXPath($doc);
        $this->directDebitXpath->registerNamespace('sepa', 'urn:iso:std:iso:20022:tech:xsd:pain.008.002.02');
    }

    /**
     * @test
     */
    public function validSumIsCalculatedCorrectly()
    {
        $this->createDirectDebitXpathObject('19.99');
        $controlSum = $this->directDebitXpath->query('//sepa:GrpHdr/sepa:CtrlSum');
        $this->assertEquals('19.99', $controlSum->item(0)->textContent, 'GroupHeader ControlSum should be 19.99');

        $controlSum = $this->directDebitXpath->query('//sepa:PmtInf/sepa:CtrlSum');
        $this->assertEquals(
            '19.99',
            $controlSum->item(0)->textContent,
            'PaymentInformation ControlSum should be 19.99'
        );
        $controlSum = $this->directDebitXpath->query('//sepa:DrctDbtTxInf/sepa:InstdAmt');
        $this->assertEquals(
            '19.99',
            $controlSum->item(0)->textContent,
            'DirectDebitTransferInformation InstructedAmount should be 19.99'
        );
    }

    /**
     * @test
     */
    public function floatSumIsCalculatedCorrectly()
    {
        $this->createDirectDebitXpathObject('19.999');
        $controlSum = $this->directDebitXpath->query('//sepa:GrpHdr/sepa:CtrlSum');
        $this->assertEquals('19.99', $controlSum->item(0)->textContent, 'GroupHeader ControlSum should be 19.99');

        $controlSum = $this->directDebitXpath->query('//sepa:PmtInf/sepa:CtrlSum');
        $this->assertEquals(
            '19.99',
            $controlSum->item(0)->textContent,
            'PaymentInformation ControlSum should be 19.99'
        );
        $controlSum = $this->directDebitXpath->query('//sepa:DrctDbtTxInf/sepa:InstdAmt');
        $this->assertEquals(
            '19.99',
            $controlSum->item(0)->textContent,
            'DirectDebitTransferInformation InstructedAmount should be 19.99'
        );
    }

    /**
     * @test
     */
    public function floatsAreAcceptedIfBcMathExtensionIsAvailable()
    {
        if(!function_exists('bcscale')) {
            $this->markTestSkipped('no bcmath extension available');
        }
        $transfer = new CustomerDirectDebitTransferInformation(
            '19.999',
            'IbanOfDebitor',
            'DebitorName'
        );

        $this->assertEquals(1999, $transfer->getTransferAmount());
    }

    /**
     * @test
     * @expectedException \Digitick\Sepa\Exception\InvalidArgumentException
     */
    public function exceptionIsThrownIfBcMathExtensionIsNotAvailableAndInputIsFloat()
    {
        if(function_exists('bcscale')) {
            $this->markTestSkipped('bcmath extension available, not possible to test exceptions');
        }
        $transfer = new CustomerDirectDebitTransferInformation(
            '19.999',
            'IbanOfDebitor',
            'DebitorName'
        );

        $this->assertEquals(1999, $transfer->getTransferAmount());
    }
}
