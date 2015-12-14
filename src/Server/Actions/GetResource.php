<?php
/**
 * Author: Nil Portugués Calderó <contact@nilportugues.com>
 * Date: 12/2/15
 * Time: 9:37 PM.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NilPortugues\Api\JsonApi\Server\Actions;

use Exception;
use NilPortugues\Api\JsonApi\Http\Request\Parameters\Fields;
use NilPortugues\Api\JsonApi\Http\Request\Parameters\Included;
use NilPortugues\Api\JsonApi\Http\Request\Parameters\Sorting;
use NilPortugues\Api\JsonApi\JsonApiSerializer;
use NilPortugues\Api\JsonApi\Server\Actions\Traits\RequestTrait;
use NilPortugues\Api\JsonApi\Server\Actions\Traits\ResponseTrait;
use NilPortugues\Api\JsonApi\Server\Errors\ErrorBag;
use NilPortugues\Api\JsonApi\Server\Errors\NotFoundError;
use NilPortugues\Api\JsonApi\Server\Query\QueryException;
use NilPortugues\Api\JsonApi\Server\Query\QueryObject;

/**
 * Class GetResource.
 */
class GetResource
{
    use RequestTrait;
    use ResponseTrait;

    /**
     * @var \NilPortugues\Api\JsonApi\Server\Errors\ErrorBag
     */
    private $errorBag;

    /**
     * @var JsonApiSerializer
     */
    private $serializer;

    /**
     * @var Fields
     */
    private $fields;

    /**
     * @var Included
     */
    private $included;

    /**
     * @param JsonApiSerializer $serializer
     * @param Fields            $fields
     * @param Included          $included
     */
    public function __construct(
        JsonApiSerializer $serializer,
        Fields $fields,
        Included $included
    ) {
        $this->serializer = $serializer;
        $this->errorBag = new ErrorBag();
        $this->fields = $fields;
        $this->included = $included;
    }

    /**
     * @param string|int $id
     * @param string     $className
     * @param callable   $callable
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function get($id, $className, callable $callable)
    {
        try {
            QueryObject::assert($this->serializer, $this->fields, $this->included, new Sorting(), $this->errorBag, $className);
            $data = $callable();

            $response = $this->response($this->serializer->serialize($data, $this->fields, $this->included));
        } catch (Exception $e) {
            $response = $this->getErrorResponse($id, $className, $e);
        }

        return $response;
    }

    /**
     * @param string|int $id
     * @param string     $className
     * @param Exception  $e
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function getErrorResponse($id, $className, Exception $e)
    {
        switch (get_class($e)) {
            case QueryException::class:
                $response = $this->errorResponse($this->errorBag);
                break;

            default:
                $mapping = $this->serializer->getTransformer()->getMappingByClassName($className);

                $response = $this->resourceNotFound(
                    new ErrorBag([new NotFoundError($mapping->getClassAlias(), $id)])
                );
        }

        return $response;
    }
}
