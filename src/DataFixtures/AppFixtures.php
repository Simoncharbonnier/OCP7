<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\Product;
use App\Entity\Client;
use App\Entity\User;

class AppFixtures extends Fixture
{
    private $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    /**
     * Load data in database
     * @param ObjectManager $manager object manager
     *
     * @return void
     */
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        $productNames = ['Galaxy Z', 'Galaxy S22', 'Redmi Note 12', 'Redmi 12 Pro', 'iPhone 15', 'iPhone 14'];
        $productBrands = ['Samsung', 'Apple', 'Xiaomi'];
        for ($i = 0; $i <= 19; $i++) {
            $product = new Product();
            $product->setName($productNames[array_rand($productNames)]);
            $product->setBrand($productBrands[array_rand($productBrands)]);
            $product->setDescription($faker->paragraph());
            $product->setPrice($faker->randomFloat(2, 500, 1500));
            $product->setReleasedAt($faker->dateTime());

            $manager->persist($product);
        }

        $clients = [];
        $clientNames = ['Bouygues Telecom', 'Orange', 'SFR', 'Free', 'La Poste'];
        $clientEmails = ['bouygues@example.com', 'orange@example.com', 'sfr@example.com', 'free@example.com', 'laposte@example.com'];
        for ($i = 0; $i <= 4; $i++) {
            $client = new Client();
            $client->setName($clientNames[$i]);
            $client->setMail($clientEmails[$i]);
            $client->setPassword($this->hasher->hashPassword($client, 'secret'));

            $clients[] = $client;
            $manager->persist($client);
        }

        for ($i = 0; $i <= 19; $i++) {
            $user = new User();
            $user->setFirstName($faker->firstName());
            $user->setLastName($faker->lastName());
            $user->setMail($faker->safeEmail());
            $user->setPhone($faker->mobileNumber());
            $user->setClient($clients[array_rand($clients)]);

            $manager->persist($user);
        }

        $manager->flush();
    }
}
