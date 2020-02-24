<?php

namespace Ultraleet\WP\WooCommerce\Components;

use WC_Product;
use InvalidArgumentException;
use Ultraleet\WP\WooCommerce\Exceptions\NotFoundException;

class Product
{
    /**
     * @var WC_Product
     */
    protected $product;

    /**
     * Product constructor.
     *
     * @param int $productId
     * @throws NotFoundException
     */
    public function __construct(int $productId = 0)
    {
        if ($productId && !$this->product = wc_get_product($productId)) {
            throw new NotFoundException("No such product: #$productId");
        }
        $this->product = $this->createWcProduct();
    }

    /**
     * Creates new WC product for composition.
     *
     * Override in child classes.
     *
     * @return WC_Product
     */
    protected function createWcProduct(): WC_Product
    {
        return new WC_Product();
    }

    /**
     * Set a prop in the WC product instance.
     *
     * @param string $name
     * @param $value
     */
    public function setProp(string $name, $value)
    {
        $setter = "set_$name";
        if (! method_exists($this->product, $setter)) {
            throw new InvalidArgumentException("Invalid prop for {$this->product->get_type()} product: $name");
        }
        $this->product->$setter($value);
    }

    /**
     * Set props in bulk in the WC product instance.
     *
     * @param array $props
     */
    public function setProps(array $props)
    {
        foreach ($props as $name => $value) {
            $this->setProp($name, $value);
        }
    }

    /**
     * Retrieve a prop from the WC product.
     *
     * @param string $name
     * @return mixed
     */
    public function getProp(string $name)
    {
        $getter = "set_$name";
        if (! method_exists($this->product, $getter)) {
            throw new InvalidArgumentException("Invalid prop for {$this->product->get_type()} product: $name");
        }
        return $this->product->$getter();
    }

    /**
     * Wrapper for setProp().
     *
     * @param string $name
     * @param $value
     */
    public function __set(string $name, $value)
    {
        $this->setProp($name, $value);
    }

    /**
     * Wrapper for getProp().
     *
     * @param string $name
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->getProp($name);
    }

    /**
     * Mixin WC product methods (useful for functions such as save()).
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        if (! method_exists($this->product, $name)) {
            throw new InvalidArgumentException("Invalid method for {$this->product->get_type()} product: $name");
        }
        return call_user_func_array([$this->product, $name], $arguments);
    }
}
