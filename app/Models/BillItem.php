<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['bill_id', 'inventory_id', 'outsourcing_company_id', 'type', 'description', 'quantity', 'cost_price', 'unit_price', 'total_price'])]
class BillItem extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function outsourcingCompany()
    {
        return $this->belongsTo(OutsourcingCompany::class);
    }
}
