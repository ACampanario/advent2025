<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Sale Entity
 *
 * @property int $id
 * @property string $order_number
 * @property string $customer_name
 * @property string $product
 * @property int $quantity
 * @property string $price
 * @property \Cake\I18n\DateTime $created
 */
class Sale extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'order_number' => true,
        'customer_name' => true,
        'product' => true,
        'quantity' => true,
        'price' => true,
        'created' => true,
    ];
}
