<?php

namespace App\DataFixtures;

use App\Entity\Wish;
use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory as FakerFactory;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class WishFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = FakerFactory::create('fr_FR');

        // Map des labels de catégories (doit correspondre à CategoryFixtures)
        $labels = [
            'Travel & Adventure',
            'Sport',
            'Entertainment',
            'Human Relations',
            'Others',
        ];

        $catRepo = $manager->getRepository(Category::class);

        for ($i = 0; $i < 3; $i++) {
            $wish = new Wish();
            $wish
                ->setTitle($faker->unique()->realText(200))
                ->setDescription($faker->optional()->paragraphs(random_int(1, 3), true))
                ->setAuthor($faker->name())
                ->setIsPublished(true)
                ->setDateCreated($faker->dateTimeBetween('-1 year', 'now'))
                ->setImage('therookie.png')
            ;

            // Assigner une catégorie aléatoire via le repository
            $label = $labels[array_rand($labels)];
            $category = $catRepo->findOneBy(['name' => $label]);
            if ($category) {
                $wish->setCategory($category);
            }

            $manager->persist($wish);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [CategoryFixtures::class];
    }
}
