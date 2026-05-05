<?php

namespace App\Livewire\Gsms;

use Livewire\Component;
use App\Models\HeavyEquipment;
use App\Models\HeavyEquipmentOfficialReceipt;

class HeavyEquipmentOfficialReceipts extends Component
{
    public HeavyEquipment $record;

    public $receipts;

    public function mount($record)
    {
       $this->receipts = HeavyEquipmentOfficialReceipt::where('heavy_equipment_id', $record->id)->get();
    }

    public function render()
    {
        return view('livewire.gsms.heavy-equipment-official-receipts');
    }
}
