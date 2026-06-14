<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Client;
use App\Entity\Company;
use App\Entity\Estimate;
use App\Entity\Project;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class NewFormFlowTest extends WebTestCase
{
    /**
     * @dataProvider newPageProvider
     */
    public function testNewPageRendersWithAWorkingStickySubmitTarget(string $route, string $formId): void
    {
        $browser = $this->createAuthenticatedBrowser();

        $crawler = $browser->request('GET', $route);

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter(\sprintf('form#%s', $formId)));
        self::assertCount(1, $crawler->filter(\sprintf('button[type="submit"][form="%s"]', $formId)));
    }

    public function testEstimateCanBeCreatedAndRedirectsToItsDetails(): void
    {
        $browser = $this->createAuthenticatedBrowser();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $client = $this->createCustomer($entityManager);
        $crawler = $browser->request('GET', '/estimate/new');
        $csrfToken = $crawler->filter('form#estimate_form input[name="estimate_form[_token]"]')->attr('value');
        $name = 'Functional estimate '.bin2hex(random_bytes(4));

        self::assertNotNull($csrfToken);
        $browser->request('POST', '/estimate/new', [
            'estimate_form' => [
                'client' => (string) $client->getId(),
                'name' => $name,
                'amount' => '1221.00',
                'startDate' => '2026-06-13',
                'endDate' => '2026-07-13',
                'status' => 'draft',
                'vatRate' => '',
                '_token' => $csrfToken,
            ],
        ]);

        self::assertResponseRedirects();
        $estimate = $entityManager->getRepository(Estimate::class)->findOneBy(['name' => $name]);
        self::assertInstanceOf(Estimate::class, $estimate);
        self::assertSame('MAD', $estimate->getCurrency());
        self::assertResponseRedirects('/estimate/'.$estimate->getId().'?created=1');
    }

    public function testProjectCanBeCreatedAndRedirectsToItsDetails(): void
    {
        $browser = $this->createAuthenticatedBrowser();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $client = $this->createCustomer($entityManager);
        $crawler = $browser->request('GET', '/project/new');
        $csrfToken = $crawler->filter('form#project_form input[name="project_form[_token]"]')->attr('value');
        $name = 'Functional project '.bin2hex(random_bytes(4));

        self::assertNotNull($csrfToken);
        $browser->request('POST', '/project/new', [
            'project_form' => [
                'client' => (string) $client->getId(),
                'name' => $name,
                'status' => 'stand_by',
                'type' => 'forfait',
                'description' => '',
                'startDate' => '2026-06-13',
                'endDate' => '2026-07-13',
                'amount' => '1500.00',
                'vatRate' => '',
                '_token' => $csrfToken,
            ],
        ]);

        self::assertResponseRedirects();
        $project = $entityManager->getRepository(Project::class)->findOneBy(['name' => $name]);
        self::assertInstanceOf(Project::class, $project);
        self::assertSame('MAD', $project->getCurrency());
        self::assertResponseRedirects('/project/'.$project->getId().'?created=1');
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function newPageProvider(): iterable
    {
        yield 'client' => ['/client/new', 'client_form'];
        yield 'collaborator' => ['/collaborateurs/new', 'collaborator_form'];
        yield 'estimate' => ['/estimate/new', 'estimate_form'];
        yield 'payment' => ['/payment/new', 'payment_form'];
        yield 'prestation' => ['/prestations/new', 'prestation_form'];
        yield 'project' => ['/project/new', 'project_form'];
        yield 'sales document' => ['/sales/document/sales-document/new', 'sales_document_form'];
        yield 'skill' => ['/skills/new', 'skill_form'];
    }

    private function createAuthenticatedBrowser(): KernelBrowser
    {
        $browser = static::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $company = (new Company())->setName('Functional Company '.bin2hex(random_bytes(4)));
        $user = (new User())
            ->setEmail('functional-'.bin2hex(random_bytes(6)).'@example.com')
            ->setPassword('unused')
            ->setCompany($company);

        $entityManager->persist($company);
        $entityManager->persist($user);
        $entityManager->flush();
        $browser->loginUser($user);

        return $browser;
    }

    private function createCustomer(EntityManagerInterface $entityManager): Client
    {
        $user = self::getContainer()->get('security.token_storage')->getToken()?->getUser();
        self::assertInstanceOf(User::class, $user);
        $company = $user->getCompany();
        self::assertInstanceOf(Company::class, $company);
        $client = (new Client())
            ->setName('Functional Client '.bin2hex(random_bytes(4)))
            ->setCompany($company);
        $client->setCurrency('MAD');

        $entityManager->persist($client);
        $entityManager->flush();

        return $client;
    }
}
