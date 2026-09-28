<?php

declare(strict_types=1);

namespace craft\contentmigrations;

use Craft;
use RuntimeException;
use craft\db\Migration;
use craft\elements\Entry;
use craft\helpers\ElementHelper;

class m260928_120000_update_menu_from_print_menu extends Migration
{
    public function safeUp(): bool
    {
        $matrixField = Craft::$app->getFields()->getFieldByHandle('menuItem');

        if (!$matrixField) {
            throw new RuntimeException('The menuItem field was not found.');
        }

        $entryTypes = $matrixField->getEntryTypes();
        $itemType = reset($entryTypes);

        if (!$itemType) {
            throw new RuntimeException('The menuItem entry type was not found.');
        }

        foreach ($this->menus() as $slug => $menu) {
            $owner = $this->section($slug);
            $owner->title = $menu['title'];
            $this->saveEntry($owner);

            $existingItems = [];
            foreach ($owner->getFieldValue('menuItem')->status(null)->all() as $existingItem) {
                $existingItems[mb_strtolower($existingItem->title)] = $existingItem;
            }

            foreach ($menu['items'] as $index => $data) {
                $menuItem = null;

                foreach (array_merge([$data[0]], $data[5] ?? []) as $title) {
                    $key = mb_strtolower($title);
                    if (isset($existingItems[$key])) {
                        $menuItem = $existingItems[$key];
                        break;
                    }
                }

                if (!$menuItem) {
                    $menuItem = new Entry();
                    $menuItem->fieldId = $matrixField->id;
                    $menuItem->typeId = $itemType->id;
                    $menuItem->setPrimaryOwner($owner);
                    $menuItem->setOwner($owner);
                    $menuItem->siteId = $owner->siteId;
                }

                $menuItem->title = $data[0];
                $menuItem->slug = ElementHelper::generateSlug($data[0]);
                $menuItem->sortOrder = $index + 1;
                $menuItem->setFieldValues([
                    'item' => $data[1],
                    'priceHero' => $data[2],
                    'priceBagel' => $data[3],
                    'priceRoll' => $data[4],
                ]);
                $this->saveEntry($menuItem);
            }
        }

        $salad = $this->section('create-your-own-salad');
        $salad->enabled = false;
        $this->saveEntry($salad);

        return true;
    }

    public function safeDown(): bool
    {
        echo "m260928_120000_update_menu_from_print_menu cannot be reverted.\n";
        return false;
    }

    private function section(string $slug): Entry
    {
        $entry = Entry::find()
            ->section('menuSection')
            ->slug($slug)
            ->status(null)
            ->one();

        if (!$entry) {
            throw new RuntimeException("Menu section not found: $slug");
        }

        return $entry;
    }

    private function saveEntry(Entry $entry): void
    {
        if (!Craft::$app->getElements()->saveElement($entry)) {
            throw new RuntimeException(sprintf(
                'Could not save "%s": %s',
                $entry->title,
                implode('; ', $entry->getErrorSummary(true)),
            ));
        }
    }

    private function menus(): array
    {
        return [
            'breakfast' => [
                'title' => 'Breakfast',
                'items' => [
                    ['Egg Only', null, '649', null, '349'],
                    ['Egg & Cheese', null, '449', null, '349'],
                    ['Grill Cheese', null, '649', null, '349', ['Grilled Cheese']],
                    ['BLT', null, '949', null, '649'],
                    ['Ham, Egg & Cheese', null, '949', null, '649'],
                    ['Bacon, Egg & Cheese', null, '999', null, '699'],
                    ['Beef Sausage, Egg & Cheese', null, '999', null, '699'],
                    ['BLT, Egg & Cheese', null, '1049', null, '749'],
                    ['Turkey Sausage, Egg & Cheese', null, '999', null, '699'],
                    ['Egg White, Turkey Bacon, Swiss & Spinach', null, '999', null, '699', ['Egg White, Bacon, Swiss & Spinach']],
                ],
            ],
            'omelettes' => [
                'title' => 'Omelettes',
                'items' => [
                    ['Meat Omelette', 'Turkey ham, sausage or bacon, onions & pepper.', '1000', null, null],
                    ['Cheese Omelette', '2 types of cheese, onions & pepper.', '999', null, null],
                    ['Western Omelette', 'Ham, onion & pepper.', '900', null, null],
                    ['Vegetable Omelette', 'Tomato, onion, pepper, mushrooms & broccoli.', '900', null, null],
                    ['Greek Omelette', 'Onion, cherry tomato, spinach & feta cheese.', '999', null, null],
                    ['Steak Omelette', null, '1099', null, null],
                ],
            ],
            'breakfast-platters' => [
                'title' => 'Breakfast Platters',
                'items' => [
                    ['Two Eggs & Home Fries', null, '799', null, null],
                    ['Bacon or Sausage, Two Eggs & Home Fries', null, '999', null, null],
                    ['Turkey Ham, Two Eggs & Home Fries', null, '999', null, null],
                    ['Turkey Bacon & Sausage, Two Eggs & Home Fries', null, '1099', null, null, ['Turkey Bacon and Sausage, Two Eggs & Home Fries']],
                    ['French Toast, Two Eggs & Sausage or Bacon', null, '999', null, null],
                ],
            ],
            'toasted' => [
                'title' => 'Toasted',
                'items' => [
                    ['Butter', null, '225', null, '125'],
                    ['Cream Cheese', null, '275', null, '149'],
                    ['Cream Cheese & Jelly', null, '299', null, '175'],
                    ['Bacon & Cream Cheese', null, '375', null, '249'],
                    ['Bacon, Cream Cheese & Jelly', null, '399', null, '375'],
                ],
            ],
            'halal-food' => [
                'title' => 'Halal Food',
                'items' => [
                    ['Chicken Over Rice', 'Lettuce & tomato.', '1399', null, null, ['Chicken']],
                    ['Lamb Over Rice', 'Lettuce & tomato.', '1499', null, null, ['Lamb']],
                    ['Mix Over Rice', 'Lettuce & tomato.', '1599', null, null, ['Chicken & Lamb']],
                    ['Chicken or Lamb Gyro', 'Lettuce & tomato.', '1099', null, null],
                ],
            ],
            'signature-sandwiches' => [
                'title' => 'Signature Sandwiches',
                'items' => [
                    ['The Torta', 'Grilled chicken, onion, pepper, beans, cheddar cheese, avocado, lettuce, tomato & chipotle mayo.', '1099', null, '899'],
                    ['The Greek Machine', 'Grilled chicken, feta cheese, spinach & olives.', '949', null, '899'],
                    ['Chicken Capri', 'Grilled chicken, roasted pepper, fresh mozzarella & pesto sauce.', '999', null, '699'],
                    ['Chicken Cordon Bleu', 'Chicken cutlet, turkey ham, Swiss cheese, lettuce, tomato & blue cheese dressing.', '1099', null, '799'],
                    ['Chicken Caesar', 'Grilled chicken, croutons, Caesar dressing, romaine lettuce & Parmesan cheese.', '999', null, '699'],
                    ['Tuna Melt', 'Classic tuna salad, cheddar cheese, lettuce & tomato.', '1199', null, '899', ['Tuina Melt']],
                    ['California Fresh', 'Grilled chicken, provolone cheese, avocado, mixed greens, lettuce, tomato & balsamic vinaigrette.', '1299', null, '899'],
                    ['Spartan Superstar', 'Grilled chicken, turkey bacon, spinach, avocado & honey garlic sauce.', '1199', null, '899'],
                    ['The Katz Special', 'Roast beef, pastrami, Swiss cheese, onion & mustard.', '1199', null, '899'],
                    ['Philly Cheesesteak', 'Beef steak, onion, pepper & American cheese.', '1099', null, '799'],
                    ['Chopped Cheese', 'Ground beef, onion, pepper & American cheese.', '999', null, '699', ['Chpped Cheese']],
                    ['Texas Steak', 'Beef steak, onion, pepper, pepper jack cheese & fried eggs.', '1199', null, '899'],
                    ['Avocado BLT', 'Avocado, crispy bacon, lettuce, tomato & mayo.', '999', null, '699'],
                    ['Roast Beef Provolone', 'Roast beef, provolone cheese, lettuce, tomato, mayo, oil & vinegar.', '1099', null, '799'],
                    ['Chicken Parm', 'Chicken cutlet, marinara sauce, mozzarella cheese & grated Parmesan cheese.', '999', null, '699'],
                    ['Turkey Club', 'Oven-gold turkey, bacon, romaine lettuce, tomato & mayo.', '999', null, '699'],
                    ['Quiz', 'Chicken cutlet, cheddar cheese, onion & BBQ sauce.', '999', null, '699'],
                    ['Gpa.', 'Chicken cutlet, American cheese, avocado, lettuce, tomato & ranch.', '1099', null, '799'],
                ],
            ],
            'wraps-panini' => [
                'title' => 'Wraps & Panini',
                'items' => [
                    ['Americano', 'Roast beef, grilled red onion, roasted pepper, American cheese & chipotle mayo.', '1099', null, null],
                    ['Big Apple Club', 'Grilled lemon chicken, mozzarella cheese, tomato, chopped basil leaves & olive oil.', '1099', null, null],
                    ['Italian Combo', 'Turkey ham, salami, provolone cheese, red onion & Italian dressing.', '1199', null, null],
                    ['Chicken Parma', 'Chicken cutlet, marinara sauce, mozzarella cheese & grated Parmesan cheese.', '1099', null, null, ['Chicken Parm']],
                    ['Philly Steak Panini', 'Beef steak, onion, pepper, American cheese & mozzarella.', '999', null, null],
                    ['Tuna Melt', 'Tuna salad & cheddar cheese.', '999', null, null],
                    ['California Wrap', 'Grilled chicken, roasted red pepper, romaine lettuce, tomato & ranch dressing.', '1099', null, null],
                    ['Big Apple Classic', 'Lemon-grilled chicken, Swiss cheese, romaine lettuce, tomato & ranch dressing.', '1099', null, null],
                    ['Chicken Quesadilla', 'Grilled chicken, onion, red & green pepper, mixed cheese; salad on the side.', '1199', null, null],
                    ['Beef Quesadilla', 'Ground beef, onion, red & green pepper, mixed cheese; salad on the side.', '1099', null, null],
                ],
            ],
            '7-days-burgers' => [
                'title' => "7 Day's Burgers",
                'items' => [
                    ['Cheese Burger', 'Cheese, lettuce, tomato & onion.', '1199', null, '949'],
                    ['Jalapeño Cheddar Burger', 'Jalapeño & cheddar cheese.', null, null, '949', ['Jalepeno Cheddar Burger']],
                    ['Mush Burger', 'Mushrooms & melted Swiss cheese.', '949', null, null, ['Mash Burger']],
                    ['Bacon Cheese Burger', 'Beef or turkey bacon & cheese.', '949', null, null],
                    ['Texas Burger', 'Onion, pepper jack cheese & fried egg.', '949', null, null],
                    ['Grill Burger', 'Double meat, two types of cheese, fried onion & pickles.', '1399', null, null],
                ],
            ],
            'fried-chicken' => [
                'title' => 'Fried Chicken',
                'items' => [
                    ['6 PCS Wings', null, '849', null, null, ['Wings - 6']],
                    ['8 PCS Wings', null, '1049', null, null, ['Wings - 8']],
                    ['10 PCS Wings', null, '1299', null, null, ['Wings -10']],
                    ['12 PCS Wings', null, '1499', null, null, ['Wings - 12']],
                    ['French Fries', null, '799', '599', null],
                    ['Curly Fries', null, '899', '699', null],
                    ['Onion Rings', null, '899', '899', null],
                    ['Seasoned Fries', null, '899', '699', null],
                    ['Sweet Potato Fries', null, '899', '699', null],
                    ['6 PCS Mozzarella Sticks', null, '699', null, null, ['Mozzarella Sticks - 6']],
                    ['8 PCS Mozzarella Sticks', null, '799', null, null, ['Mozzarella Sticks - 8']],
                    ['Chicken Cutlet', 'Per piece.', '249', null, null],
                    ['Chicken Nuggets', 'Per piece.', '99', null, null],
                    ['Chicken Tenders', 'Per piece.', '175', null, null],
                ],
            ],
        ];
    }
}
