<?php

namespace Appnings\Payment\Gateways;

//here is the source code for encrypting the request
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Appnings\Payment\Exceptions\PaymentGatewayException;
use Mockery\Exception;


class CCAvenueGateway implements PaymentGatewayInterface
{
    protected $parameters = array();
    protected $merchantData = '';
    protected $encRequest = '';
    protected $testMode = false;
    protected $workingKey = '';
    protected $accessCode = '';
    protected $liveEndPoint = 'https://secure.ccavenue.com/transaction/transaction.do?command=';
    protected $testEndPoint = 'https://test.ccavenue.com/transaction/transaction.do?command=';

    //as per documentation issued by ccavenue on 10-02-2017 API VER 1.1
    protected $apiLiveEndPoint = 'https://api.ccavenue.com/apis/servlet/DoWebTrans?';
    protected $apiTestEndPoint = 'https://apitest.ccavenue.com/apis/servlet/DoWebTrans?';
    protected $apiVersion = '1.1';
    protected $apiRequestType = 'JSON';

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

    public function getAPIEndPoint()
    {
        return $this->testMode ? $this->apiTestEndPoint : $this->apiLiveEndPoint;
    }

    public function initializeApiRequest($testEndpoint = '', $liveEndpoint = '', $apiVersion = '1.1')
    {
        if ($liveEndpoint) {
            $this->apiLiveEndPoint = $liveEndpoint;
        }

        if ($testEndpoint) {
            $this->apiTestEndPoint = $testEndpoint;
        }

        if ($apiVersion) {
            $this->apiVersion = $apiVersion;
        }

        return $this;
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
     * Function to initiate transaction
     * @return mixed
     */
    public function send()
    {
        Log::info('Appnings || CCAvenue payment gateway initialized : ');

        return View::make('vendor.payment.ccavenue')
            ->with('encRequest', $this->encRequest)
            ->with('accessCode', $this->accessCode)
            ->with('endPoint', $this->getEndPoint() . "initiateTransaction");
    }

    /**
     * Check Response
     * @param $request
     * @return array
     */
    public function response($request)
    {
//        dd($request->all());

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

    /**
     * Function to check the status of the given order number
     * @param $order_number Order number for which the status needs to be checked!
     * @param int $transaction_id transaction reference by ccavenue for which the status needs to be checked!
     */
    public function getOrderDetails($order_number, $transaction_id = 0)
    {
        $merchant_data = [];

        $response_string = '';

        $order_data = [];

        if ($transaction_id) {
            $merchant_data['reference_no'] = $transaction_id;
        } else if ($order_number) {
            $merchant_data['order_no'] = $order_number;
        }


        if ($merchant_data) {

            $encRequest = $this->encrypt(json_encode($merchant_data), env('CCAVENUE_WORKING_KEY'));

            $client = new \GuzzleHttp\Client();

            $order_status_params = [
                'enc_request' => $encRequest,
                'access_code' => env('CCAVENUE_ACCESS_CODE'),
                'command' => 'orderStatusTracker',
                'request_type' => "JSON",
                'version' => "1.1"
            ];

            $request_parameters = http_build_query($order_status_params);

            try {

                //making request to to CCAvenue server with the prepared parameters
                $response_string = $client->post($this->getAPIEndPoint() . $request_parameters);

                //ccavenue reseponsds with a serialized url response which should be parsed
                parse_str($response_string->getBody()->getContents(), $order_data);

                return $this->getOrderStatus($order_data);


            } catch (BadResponseException $e) {
                dd("Error occured" . $e->getMessage());

            } catch (ConnectException $e) { // Wrong URL pinged or server not responding
                dd("Error occured" . $e->getMessage());

            } catch (ClientException $e) { // URL Response error
                dd("Error occured" . $e->getMessage());

            }

            return false;
        }

    }


    /**
     * @param array $parsedData
     */
    private function getOrderStatus($parsedData = [])
    {

        try {

            $decrypted_response = $this->decrypt(str_replace(["\n", "\r"], '', $parsedData['enc_response']), $this->workingKey);

        } catch (Exception $e) {

            infoPlus(["Exception while decrepting the enc_response", $e]);

            return false;

        }

        $order = json_decode($decrypted_response, TRUE);

        return $order;
    }
}