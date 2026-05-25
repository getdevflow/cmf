<?php

declare(strict_types=1);

namespace Application\Service\Forms;

use Exception;
use Qubus\Form\Form;
use Qubus\Form\FormBuilder\Decorator\Bootstrap;
use Qubus\Form\FormView;

/**
 * @deprecated since version 2.1.0. Use the CustomFields plugin instead.
 */
class ProductForm extends FormView
{
    protected array $opts = [
        'form-tag' => false,
        'decorate' => true,
    ];

    /**
     * @param array|null $values Values set by a request or pulled from the database.
     * @param string|null $productId The product ID to check against.
     * @return Form
     * @throws Exception
     */
    public function buildForm(?array $values = null, ?string $productId = null): Form
    {
        return $this
            ->addDecorator(new Bootstrap(['version' => 3]));
    }
}
