<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture
{
    public const CATEGORY_TRAVEL = 'category_travel';
    public const CATEGORY_SPORT = 'category_sport';
    public const CATEGORY_ENTERTAINMENT = 'category_entertainment';
    public const CATEGORY_HUMAN = 'category_human';
    public const CATEGORY_OTHERS = 'category_others';

    public function load(ObjectManager $manager): void
    {
        $names = [
            self::CATEGORY_TRAVEL => 'Travel & Adventure',
            self::CATEGORY_SPORT => 'Sport',
            self::CATEGORY_ENTERTAINMENT => 'Entertainment',
            self::CATEGORY_HUMAN => 'Human Relations',
            self::CATEGORY_OTHERS => 'Others',
        ];

        $repo = $manager->getRepository(Category::class);

        foreach ($names as $ref => $label) {
            $category = $repo->findOneBy(['name' => $label]);

            if (! $category) {
                $category = new Category();
                $category->setName($label);
                $manager->persist($category);
            }

            // Ajoute la référence même si la catégorie existait déjà
            $this->addReference($ref, $category);
        }

        $manager->flush();
    }
}
