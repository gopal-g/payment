<?php

namespace Appnings\Payment\Gateways;

//here is the source code for encrypting the request
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Appnings\Payment\Exceptions\PaymentGatewayException;


class CCAvenueGateway implements PaymentGatewayInterface
{
    protected $parameters = array();
    protected $merchantData = '';
    protected $encRequest = '';
    protected $testMode = false;
    protected $workingKey = '';
    protected $accessCode = '';
    protected $liveEndPoint = 'https://secure.ccavenue.com/transaction/transaction.do?command=initiateTransaction';
    protected $testEndPoint = 'https://test.ccavenue.com/transaction/transaction.do?command=initiateTransaction';
    public $response = '';

    public function __construct()
    {
        $this->workingKey = Config::get('payment.ccavenue.workingKey');
        $this->accessCode = Config::get('payment.ccavenue.accessCode');
        $this->testMode = Config::get('payment.testMode');
        $this->parameters['merchant_id'] = Config::get('payment.ccavenue.merchantId');
        $this->parameters['currency'] = Config::get('payment.ccavenue.currency');
        $this->parameters['redirect_url'] = url(Config::get('payment.ccavenue.redirectUrl'));
        $this->parameters['cancel_url'] = url(Config::get('payment.ccavenue.cancelUrl'));
        $this->parameters['language'] = Config::get('payment.ccavenue.language');
    }

    public function getEndPoint()
    {
        return $this->testMode ? $this->testEndPoint : $this->liveEndPoint;
    }

    public function request($parameters)
    {
        $this->parameters = array_merge($this->parameters, $parameters);

        $this->checkParameters($this->parameters);

        foreach ($this->parameters as $key => $value) {
            $this->merchantData .= $key . '=' . $value . '&';
        }

        $this->encRequest = $this->encrypt($this->merchantData, $this->workingKey);

        return $this;
    }

    /**
     * @return mixed
     */
    public function send()
    {
        Log::info('Appnings || CCAvenue payment gateway initialized : ');

        return View::make('vendor.payment.ccavenue')
            ->with('encRequest', $this->encRequest)
            ->with('accessCode', $this->accessCode)
            ->with('endPoint', $this->getEndPoint());
    }

    /**
     * Check Response
     * @param $request
     * @return array
     */
    public function response($request)
    {
        $encResponse = $request->encResp;

        $rcvdString = $this->decrypt($encResponse, $this->workingKey);

        parse_str($rcvdString, $decResponse);

        return $decResponse;
    }

    /**
     * @param $parameters
     * @throws IndipayParametersMissingException
     */
    public function checkParameters($parameters)
    {
        $validator = Validator::make($parameters, [
            'merchant_id' => 'required',
            'currency' => 'required',
            'redirect_url' => 'required|url',
            'cancel_url' => 'required|url',
            'language' => 'required',
            'order_id' => 'required',
            'amount' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            throw new PaymentGatewayException($validator->errors()->toJson());
        }
    }

    /**
     * Function to decrypt
     * @param $encryptedText string
     * @param $key
     * @return string
     */
    public function decrypt($encryptedText, $key)
    {
        $key = $this->hextobin(md5($key));
        $initVector = pack("C*", 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0a, 0x0b, 0x0c, 0x0d, 0x0e, 0x0f);
        $encryptedText = $this->hextobin($encryptedText);
        $decryptedText = openssl_decrypt($encryptedText, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $initVector);
        return $decryptedText;
    }

    /**
     * Function to encrypt
     * @param $plainText string
     * @param $key string
     * @return string
     */
    public function encrypt($plainText, $key)
    {
        $key = $this->hextobin(md5($key));
        $initVector = pack("C*", 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0a, 0x0b, 0x0c, 0x0d, 0x0e, 0x0f);
        $openMode = openssl_encrypt($plainText, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $initVector);
        $encryptedText = bin2hex($openMode);
        return $encryptedText;
    }

    /**
     * Padding function
     * @param $plainText string
     * @param $blockSize integer
     * @return string
     */
    public function pkcs5_pad($plainText, $blockSize)
    {
        $pad = $blockSize - (strlen($plainText) % $blockSize);
        return $plainText . str_repeat(chr($pad), $pad);
    }

    /**
     * Function to convert hexadecimal to binary
     * @param $hexString
     * @return string
     */
    public function hextobin($hexString)
    {
        $length = strlen($hexString);
        $binString = "";
        $count = 0;
        while ($count < $length) {
            $subString = substr($hexString, $count, 2);
            $packedString = pack("H*", $subString);
            if ($count == 0) {
                $binString = $packedString;
            } else {
                $binString .= $packedString;
            }

            $count += 2;
        }
        return $binString;
    }
}
