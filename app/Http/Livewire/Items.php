<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Item;
use Livewire\WithPagination;

class Items extends Component
{
    use WithPagination;

    public $active;
    public $q;
    public $sortBy = 'id';
    public $sortAsc = true;
    public $item;
    
    // Confirmation modals
    public $confirmingItemDeletion = false;
    public $confirmingItemAdd = false;
    public $confirmingCheckOut = false;

    // Item IDs for checkout
    public $checkoutItemIds = [];

    // Livewire query string
    protected $queryString = [
        'active' => ['except' => false],
        'q' => ['except' => ''],
        'sortBy' => ['except' => 'id'],
        'sortAsc' => ['except' => true],
    ];

    // Validation rules
    protected $rules = [
        'item.name' => 'required|string|min:4',
        'item.price' => 'required|numeric|between:1,100',
        'item.status' => 'boolean',
    ];

    public function render()
    {
        $items = Item::where('user_id', auth()->user()->id)
            ->when($this->q, function ($query) {
                return $query->where(function ($query) {
                    $query->where('name', 'like', '%' . $this->q . '%')
                        ->orWhere('price', 'like', '%' . $this->q . '%');
                });
            })
            ->when($this->active, function ($query) {
                return $query->active();
            })
            ->orderBy($this->sortBy, $this->sortAsc ? 'ASC' : 'DESC')
            ->paginate(10);

        return view('livewire.items', ['items' => $items]);
    }

    // Methods for updating properties
    public function updatingActive()
    {
        $this->resetPage();
    }

    public function updatingQ()
    {
        $this->resetPage();
    }

    // Method for sorting
    public function sortBy($field)
    {
        if ($field == $this->sortBy) {
            $this->sortAsc = !$this->sortAsc;
        }
        $this->sortBy = $field;
    }

    // Methods for item deletion
    public function confirmItemDeletion($id)
    {
        $this->confirmingItemDeletion = $id;
    }

    public function deleteItem(Item $item)
    {
        $item->delete();
        $this->confirmingItemDeletion = false;
        session()->flash('message', 'Item Deleted Successfully');
    }

    // Methods for item addition and edition
    public function confirmItemAdd()
    {
        $this->reset(['item']);
        $this->confirmingItemAdd = true;
    }

    public function confirmItemEdit(Item $item)
    {
        $this->resetErrorBag();
        $this->item = $item;
        $this->confirmingItemAdd = true;
    }

    public function saveItem()
    {
        $this->validate();

        if (isset($this->item->id)) {
            $this->item->save();
            session()->flash('message', 'Item Saved Successfully');
        } else {
            auth()->user()->items()->create([
                'name' => $this->item['name'],
                'price' => $this->item['price'],
                'status' => $this->item['status'] ?? 0,
            ]);
            session()->flash('message', 'Item Added Successfully');
        }

        $this->confirmingItemAdd = false;
    }

    // Methods for checkout
    public function confirmCheckOut($itemId)
    {
        $this->confirmingCheckOut = true;
        $this->checkoutItemIds[] = $itemId;
    }

    public function checkOutItem()
    {
        foreach ($this->checkoutItemIds as $itemId) {
            $item = Item::find($itemId);

            if ($item && $item->status) {
                $item->update([
                    'status' => false,
                    'checked_out' => true,
                    'checked_out_at' => now(),
                ]);

        }

        $this->confirmingCheckOut = false;
        $this->checkoutItemIds = [];

        $this->redirect('https://paystack.com/pay/39gjzf-f2x');
    }
}

}