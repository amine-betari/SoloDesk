<?php

declare(strict_types=1);

namespace App\Tests\Form\AutoComplete;

use App\Entity\Client;
use App\Entity\Company;
use App\Entity\User;
use App\Form\AutoComplete\ClientAutocompleteField;
use App\Form\AutoComplete\InvoiceAutocompleteField;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CompanyScopedAutocompleteTest extends TestCase
{
    public function testClientAutocompleteFiltersByCurrentCompany(): void
    {
        $company = new Company();
        $field = new ClientAutocompleteField($this->createSecurity($company));
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $repository = $this->createMock(EntityRepository::class);

        $repository
            ->expects(self::once())
            ->method('createQueryBuilder')
            ->with('client')
            ->willReturn($queryBuilder);
        $queryBuilder
            ->expects(self::once())
            ->method('andWhere')
            ->with('client.company = :company')
            ->willReturnSelf();
        $queryBuilder
            ->expects(self::once())
            ->method('setParameter')
            ->with('company', $company)
            ->willReturnSelf();
        $queryBuilder
            ->expects(self::once())
            ->method('orderBy')
            ->with('client.name', 'ASC')
            ->willReturnSelf();

        $options = $this->resolveOptions($field);
        $options['query_builder']($repository);
    }

    public function testClientAutocompleteExposesClientCurrency(): void
    {
        $field = new ClientAutocompleteField($this->createSecurityWithoutExpectation());
        $client = (new Client())->setName('Client');
        $client->setCurrency('MAD');

        $options = $this->resolveOptions($field);

        self::assertSame(['data-currency' => 'MAD'], $options['choice_attr']($client));
    }

    public function testInvoiceAutocompleteFiltersByCurrentCompany(): void
    {
        $company = new Company();
        $field = new InvoiceAutocompleteField($this->createSecurity($company));
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $repository = $this->createMock(EntityRepository::class);

        $repository
            ->expects(self::once())
            ->method('createQueryBuilder')
            ->with('s')
            ->willReturn($queryBuilder);
        $queryBuilder
            ->expects(self::exactly(2))
            ->method('andWhere')
            ->willReturnCallback(function (string $condition) use ($queryBuilder): QueryBuilder {
                self::assertContains($condition, [
                    's.company = :company',
                    's.type = :type1 OR s.type = :type2',
                ]);

                return $queryBuilder;
            });
        $queryBuilder
            ->expects(self::exactly(3))
            ->method('setParameter')
            ->willReturnCallback(function (string $name, mixed $value) use ($company, $queryBuilder): QueryBuilder {
                $expected = [
                    'company' => $company,
                    'type1' => 'invoice',
                    'type2' => 'project',
                ];
                self::assertArrayHasKey($name, $expected);
                self::assertSame($expected[$name], $value);

                return $queryBuilder;
            });
        $queryBuilder
            ->expects(self::once())
            ->method('orderBy')
            ->with('s.reference', 'ASC')
            ->willReturnSelf();

        $options = $this->resolveOptions($field);
        $options['query_builder']($repository);
    }

    private function createSecurity(Company $company): Security
    {
        $user = (new User())->setCompany($company);
        $security = $this->createMock(Security::class);
        $security
            ->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        return $security;
    }

    private function createSecurityWithoutExpectation(): Security
    {
        return $this->createMock(Security::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveOptions(ClientAutocompleteField|InvoiceAutocompleteField $field): array
    {
        $resolver = new OptionsResolver();
        $field->configureOptions($resolver);

        return $resolver->resolve();
    }
}
