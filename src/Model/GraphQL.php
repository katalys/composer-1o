<?php

namespace OneO\Model;

class GraphQL
{
    protected $client;

    public function __construct($url, $bearerToken)
    {
        $this->client = new \GraphQL\Client($url, ['Authorization' => "Bearer $bearerToken"]);
    }

    public function healthCheck()
    {
        $gql = (new \GraphQL\Query('healthCheck'));
        return $this->client->runQuery($gql)->getResponseBody();
    }

    public function createProduct($product) {
        $gql = $mutation = (new \GraphQL\Mutation('createProduct'))
            ->setVariables([new \GraphQL\Variable('input', 'ProductInput', true)])
            ->setArguments(['input' => '$input'])
            ->setSelectionSet(
                ['id']
            );

        return $this->client->runQuery($gql, true, ['input' => $product]);
    }

    public function getProductBySpecificId($identificatorName, $idValue) {
        switch($identificatorName) {
            case 'id':
                $type = "ID";
                break;
            default:
                $type = "String";
                break;
        }

        $gql = (new \GraphQL\Query('product'))
            ->setVariables([new \GraphQL\Variable($identificatorName, $type, true)])
            ->setArguments([$identificatorName => '$'.$identificatorName])
            ->setSelectionSet([
                'id'
            ]);

        return $this->client->runQuery($gql, true, [$identificatorName => $idValue])->getResponseBody();
    }

    public function getProductByExternalId($id)
    {
        return $this->getProductBySpecificId("externalId", $id);
    }

    public function getProductById($id)
    {
        return $this->getProductBySpecificId("id", $id);
    }

}