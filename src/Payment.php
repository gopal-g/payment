<?php

namespace Appnings\Payment;

use Appnings\Payment\Gateways\CCAvenueGateway;
use Appnings\Payment\Gateways\PaymentGatewayInterface;

class Payment
{

    protected $gateway;

    /**
     * @param PaymentGatewayInterface $gateway
     */
    public function __construct(PaymentGatewayInterface $gateway)
    {
        $this->gateway = $gateway;
    }

    public function purchase($parameters = array())
    {

        return $this->gateway->request($parameters)->send();

    }

    public function response($request)
    {
        return $this->gateway->response($request);
    }

    public function prepare($parameters = array())
    {
        return $this->gateway->request($parameters);
    }

    public function process($order)
    {
        return $order->send();
    }

    public function gateway($name)
    {
        switch ($name) {
            case 'CCAvenue':
                $this->gateway = new CCAvenueGateway();
                break;
        }

        return $this;
    }

    public function getOrderDetails($parameters)
    {
        return $this->gateway->getOrderDetails($parameters);
    }

}
