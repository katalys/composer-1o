<?php

namespace OneO\Model;

use GraphQL\Client;

/**
 * GraphQL class
 */
class GraphQL
{
    /**
     * @var array
     */
    protected $authorization;

    /**
     * @var KatalysToken
     */
    protected $katalysToken;

    /**
     * @var string
     */
    protected $url;

    /**
     * @param string $url
     * @param string $bearerToken
     * @param string|null $katalysToken
     */
    public function __construct(string $url, string $bearerToken, KatalysToken $katalysToken = null)
    {
        $this->url = $url;
        $this->authorization = ['Authorization' => "Bearer $bearerToken"];
        if ($katalysToken) {
            $this->katalysToken = $katalysToken;
        }
    }

    /**
     * @param string $query
     * @param array|null $variables
     * @return Client
     * @throws \Exception
     */
    protected function getClient(string $query, array $variables = null): Client
    {
        if ($this->katalysToken) {
            $variable = ['query' => (string) $query];
            if ($variables) {
                $variable['variables'] = $variables;
            }
            $query = str_replace(
                "\n",
                ' ',
                \json_encode($variable)
            );
            $token = $this->katalysToken->createToken($query);
            $this->authorization['x-katalys-token'] = $token;
        }
        return new Client($this->url, $this->authorization);
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function healthCheck()
    {
        $gql = (new \GraphQL\Query('healthCheck'));
        return $this->getClient($gql)->runQuery($gql)->getResponseBody();
    }

    /**
     * @param $product
     * @return mixed
     * @throws \Exception
     */
    public function createProduct($product)
    {
        $gql = $mutation = (new \GraphQL\Mutation('createProduct'))
            ->setVariables([new \GraphQL\Variable('input', 'ProductInput', true)])
            ->setArguments(['input' => '$input'])
            ->setSelectionSet(
                ['id']
            );

        $variables = ['input' => $product];
        return \json_decode(
            $this->getClient($gql, $variables)->runQuery(
                $gql,
                true,
                $variables
            )->getResponseBody(), true);
    }

    /**
     * @param $identificatorName
     * @param $idValue
     * @return string
     * @throws \Exception
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

        $variables = [$identificatorName => $idValue];
        return $this->getClient($gql, $variables)->runQuery(
            $gql,
            true,
            $variables
        )->getResponseBody();
    }

    /**
     * @param $id
     * @return string
     * @throws \Exception
     */
    public function getProductByExternalId($id)
    {
        return $this->getProductBySpecificId("externalId", $id);
    }

    /**
     * @param $id
     * @return string
     * @throws \Exception
     */
    public function getProductById($id)
    {
        return $this->getProductBySpecificId("id", $id);
    }

    /**
     * @param $id
     * @return mixed
     * @throws \Exception
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

        $variables = ['id' => $id];
        return \json_decode(
            $this->getClient($gql, $variables)->runQuery(
                $gql,
                true,
                $variables
            )->getResponseBody(),
            true
        )["data"]["order"];
    }

    /**
     * @param $orderId
     * @param $shippingRates
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

        $variables = [
            'id' => $orderId,
            'input' => ["shippingRates" => $shippingRates]
        ];
        return \json_decode($this->getClient($gql, $variables)->runQuery(
            $gql,
            true,
            $variables
        )->getResponseBody(), true);
    }

    /**
     * @param $orderId
     * @param $taxes
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

        $variables = [
            'id' => $orderId,
            'input' => $taxes
        ];
        return \json_decode($this->getClient($gql, $variables)->runQuery(
            $gql,
            true,
            $variables
        )->getResponseBody(), true);
    }

    /**
     * @param $orderId
     * @param $availabilities
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

        $variables = [
            'id' => $orderId,
            'input' => [
                'line_items' => $availabilities
            ]
        ];
        return \json_decode($this->getClient($gql, $variables)->runQuery(
            $gql,
            true,
            $variables
        )->getResponseBody(), true);
    }

    /**
     * @param $orderId
     * @param $externalOrderId
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

        $variables = [
            'id' => $orderId,
            'input' => [
                "externalId" => $externalOrderId,
                "fulfillmentStatus" => "FULFILLED"
            ]
        ];
        return \json_decode($this->getClient($gql, $variables)->runQuery(
            $gql,
            true,
            $variables
        )->getResponseBody(), true);
    }
}