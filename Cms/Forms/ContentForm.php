<?php

declare(strict_types=1);

namespace Cms\Forms;

use Exception;
use Qubus\Form\Form;
use Qubus\Form\FormBuilder\Decorator\Bootstrap;
use Qubus\Form\FormView;

class ContentForm extends FormView
{
    protected array $opts = [
        'form-tag' => false,
        'decorate' => true,
    ];

    /**
     * @param array|null $values
     * @param string|null $contentType
     * @param string|null $contentId
     * @return Form
     * @throws Exception
     */
    public function buildForm(?array $values = null, ?string $contentType = null, ?string $contentId = null): Form
    {
        return $this
            ->addDecorator(new Bootstrap(['version' => 3]));
    }
}
