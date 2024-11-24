<?php

declare(strict_types=1);

namespace App\Shared\Services;

use Qubus\Form\Form;
use Qubus\Form\FormView;

class GeneralOptionForm extends FormView
{
    protected array $opts = [
        'form-tag' => false,
        'decorate' => true,
    ];

    public function buildForm(): Form
    {
        return $this;
    }
}
