<?php

declare(strict_types=1);

namespace App\Tests\Template;

use PHPUnit\Framework\TestCase;

final class NewFormContractTest extends TestCase
{
    public function testStickySubmitButtonUsesTheProvidedFormId(): void
    {
        $contents = $this->readTemplate('components/_new_form_actions.html.twig');

        self::assertStringContainsString('type="submit"', $contents);
        self::assertStringContainsString('form="{{ form_id }}"', $contents);
    }

    /**
     * @dataProvider newFormTemplateProvider
     */
    public function testStickySubmitTargetsAnExistingFormId(string $template): void
    {
        $contents = $this->readTemplate($template);

        self::assertStringContainsString("'id': form.vars.id", $contents);
        self::assertStringContainsString("'components/_new_form_actions.html.twig'", $contents);
        self::assertStringContainsString('form_id: form.vars.id', $contents);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function newFormTemplateProvider(): iterable
    {
        yield 'client' => ['client/_form.html.twig'];
        yield 'collaborator' => ['collaborator/_form.html.twig'];
        yield 'estimate' => ['estimate/_form.html.twig'];
        yield 'payment' => ['payment/_form.html.twig'];
        yield 'prestation' => ['prestation/_form.html.twig'];
        yield 'project' => ['project/_form.html.twig'];
        yield 'sales document' => ['sales_document/_form.html.twig'];
        yield 'skill' => ['skill/_form.html.twig'];
    }

    /**
     * @dataProvider nullableCurrencyTemplateProvider
     */
    public function testNewFinancialFormDoesNotReadNullableEntityCurrency(string $template, string $entity): void
    {
        $contents = $this->readTemplate($template);

        self::assertStringContainsString(
            \sprintf("'data-new-form-currency-value': %s.client ? %s.client.currency : ''", $entity, $entity),
            $contents
        );
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function nullableCurrencyTemplateProvider(): iterable
    {
        yield 'estimate' => ['estimate/_form.html.twig', 'estimate'];
        yield 'project' => ['project/_form.html.twig', 'project'];
    }

    private function readTemplate(string $template): string
    {
        $contents = file_get_contents(\dirname(__DIR__, 2).'/templates/'.$template);

        self::assertNotFalse($contents);

        return $contents;
    }
}
