<?php

/**
 * This file is part of Milpa Live — the render-target-agnostic live component core of the Milpa PHP framework.
 *
 * (c) Rodrigo Vicente - TeamX Agency — https://teamx.agency <hola@teamx.agency>
 *
 * @license Apache-2.0
 *
 * @link    https://github.com/getmilpa/live
 */

declare(strict_types=1);

namespace Milpa\Live\Tests\Schema;

use Milpa\Live\Schema\FieldError;
use Milpa\Live\Schema\FormSubmission;
use Milpa\Live\Schema\ValidationResult;
use PHPUnit\Framework\TestCase;

final class ValidationResultTest extends TestCase
{
    public function test_error_carries_code_and_message(): void
    {
        $e = new FieldError('required', 'Site name is required.');
        self::assertSame('required', $e->code);
        self::assertSame('Site name is required.', $e->message);
    }

    public function test_submission_holds_values_and_validation(): void
    {
        $result = new ValidationResult(false, ['siteName' => [new FieldError('required', 'x')]]);
        $sub = new FormSubmission(['siteName' => null, 'retries' => 3], $result);

        self::assertFalse($sub->validation->ok);
        self::assertSame('required', $sub->validation->errors['siteName'][0]->code);
        self::assertSame(3, $sub->values['retries']);
    }
}
