<?php

namespace OneO\Model\Model;

/**
 * GraphQL class
 */
class GraphQL
{
    /**
     * @var \GraphQL\Client
     */
    protected $client;

    /**
     * @param string $url
     * @param string $bearerToken
     */
    public function __construct($url, $bearerToken)
    {
        $this->client = new \GraphQL\Client($url, ['Authorization' => "Bearer $bearerToken"]);
    }

    /**
     * @return string
     */
    public function healthCheck()
    {
        $gql = (new \GraphQL\Query('healthCheck'));
        return $this->client->runQuery($gql)->getResponseBody();
    }

    /**
     * @param $product
     * @return mixed
     */
    public function createProduct($product)
    {
        $gql = $mutation = (new \GraphQL\Mutation('createProduct'))
            ->setVariables([new \GraphQL\Variable('input', 'ProductInput', true)])
            ->setArguments(['input' => '$input'])
            ->setSelectionSet(
                ['id']
            );

        return json_decode(
            $this->client->runQuery(
                $gql,
                true,
                ['input' => $product]
            )->getResponseBody(), true);
    }

    /**
     * @param string $identificatorName
     * @param string $idValue
     * @return string
     */
    public function getProductBySpecificId($identificatorName, $idValue)
    {
        switch ($identificatorName) {
            case 'id':
                $type = "ID";
                break;
            default:
                $type = "String";
                break;
        }

        $gql = (new \GraphQL\Query('product'))
            ->setVariables([new \GraphQL\Variable($identificatorName, $type, true)])
            ->setArguments([$identificatorName => '$' . $identificatorName])
            ->setSelectionSet([
                'id'
            ]);

        return $this->client->runQuery($gql, true, [$identificatorName => $idValue])->getResponseBody();
    }

    /**
     * @param string $id
     * @return string
     */
    public function getProductByExternalId($id)
    {
        return $this->getProductBySpecificId("externalId", $id);
    }

    /**
     * @param string $id
     * @return string
     */
    public function getProductById($id)
    {
        return $this->getProductBySpecificId("id", $id);
    }

    /**
     * @param string $id
     * @return mixed
     */
    public function getOrderDetails($id)
    {
        $gql = (new \GraphQL\Query('order'))
            ->setVariables([new \GraphQL\Variable("id", "ID", true)])
            ->setArguments(["id" => '$id'])
            ->setSelectionSet([
                'id',
                'externalId',
                (new \GraphQL\Query('lineItems'))
                    ->setSelectionSet(
                        [
                            'id',
                            'productExternalId',
                            'variantExternalId',
                            'quantity',
                            'price'
                        ]
                    ),
                'shippingAddressZip',
                'shippingAddressCity',
                'shippingAddressCountry',
                'shippingAddressCountryCode',
                'shippingAddressLine_1',
                'shippingAddressLine_2',
                'shippingName',
                'shippingEmail',
                'shippingPhone',
                'shippingAddressSubdivision',
                'shippingAddressSubdivisionCode',
                'billingName',
                'billingEmail',
                'billingPhone',
                'billingAddressZip',
                'billingAddressCity',
                'billingAddressCountry',
                'billingAddressCountryCode',
                'billingAddressLine_1',
                'billingAddressLine_2',
                'billingAddressSubdivision',
                'billingAddressSubdivisionCode',
                'chosenShippingRateHandle'
            ]);

        return json_decode(
            $this->client->runQuery(
                $gql,
                true,
                ['id' => $id]
            )->getResponseBody(), true)["data"]["order"];
    }

    /**
     * @param string $orderId
     * @param array $shippingRates
     * @return mixed
     */
    public function updateShippingRates($orderId, $shippingRates)
    {
        $gql = (new \GraphQL\Mutation('updateOrder'))
            ->setVariables(
                [
                    new \GraphQL\Variable('id', 'ID', true),
                    new \GraphQL\Variable('input', 'OrderInput', true)
                ]
            )
            ->setArguments(['id' => '$id', 'input' => '$input'])
            ->setSelectionSet(
                [
                    'id',
                    (new \GraphQL\Query('shippingRates'))
                        ->setSelectionSet(
                            [
                                'handle',
                                'amount',
                                'title'
                            ]
                        ),
                ]
            );

        return json_decode($this->client->runQuery(
            $gql,
            true,
            [
                'id' => $orderId,
                'input' => ["shippingRates" => $shippingRates]
            ]
        )->getResponseBody(), true);
    }

    /**
     * @param string $orderId
     * @param string $taxes
     * @return mixed
     */
    public function updateTaxes($orderId, $taxes)
    {
        $gql = (new \GraphQL\Mutation('updateOrder'))
            ->setVariables(
                [
                    new \GraphQL\Variable('id', 'ID', true),
                    new \GraphQL\Variable('input', 'OrderInput', true)
                ]
            )
            ->setArguments(['id' => '$id', 'input' => '$input'])
            ->setSelectionSet(
                [
                    'totalTax',
                    (new \GraphQL\Query('lineItems'))
                        ->setSelectionSet(
                            [
                                'tax'
                            ]
                        ),
                ]
            );

        return json_decode($this->client->runQuery(
            $gql,
            true,
            [
                'id' => $orderId,
                'input' => $taxes
            ]
        )->getResponseBody(), true);
    }

    /**
     * @param string $orderId
     * @param array $availabilities
     * @return mixed
     */
    public function updateAvailabilities($orderId, $availabilities)
    {
        $gql = (new \GraphQL\Mutation('updateOrder'))
            ->setVariables(
                [
                    new \GraphQL\Variable('id', 'ID', true),
                    new \GraphQL\Variable('input', 'OrderInput', true)
                ]
            )
            ->setArguments(['id' => '$id', 'input' => '$input'])
            ->setSelectionSet(
                [
                    (new \GraphQL\Query('lineItems'))
                        ->setSelectionSet(
                            [
                                'id',
                                'available'
                            ]
                        ),
                ]
            );

        return json_decode($this->client->runQuery(
            $gql,
            true,
            [
                'id' => $orderId,
                'input' => [
                    'line_items' => $availabilities
                ]
            ]
        )->getResponseBody(), true);
    }

    /**
     * @param string $orderId
     * @param string $externalOrderId
     * @return mixed
     */
    public function completeOrder($orderId, $externalOrderId)
    {
        $gql = (new \GraphQL\Mutation('updateOrder'))
            ->setVariables(
                [
                    new \GraphQL\Variable('id', 'ID', true),
                    new \GraphQL\Variable('input', 'OrderInput', true)
                ]
            )
            ->setArguments(['id' => '$id', 'input' => '$input'])
            ->setSelectionSet(
                [
                    'id',
                    'fulfillmentStatus'
                ]
            );

        return json_decode($this->client->runQuery(
            $gql,
            true,
            [
                'id' => $orderId,
                'input' => [
                    "externalId" => $externalOrderId,
                    "fulfillmentStatus" => "FULFILLED"
                ]
            ]
        )->getResponseBody(), true);
    }
}