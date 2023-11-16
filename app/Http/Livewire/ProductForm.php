<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Country;
use App\Models\Product;
use App\Models\Category;
use Livewire\Redirector;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ProductForm extends Component
{
    public Product $product;

    public bool $editing = false; //for knowing if the form is created or edited.

    public array $categories = [];//for binding selected categories in the form.

    public array $listsForFields = [];//will have an array of needed values to pass into Select2, in our case countries and categories list.

    public function mount(Product $product): void
    {
        $this->product = $product;

        $this->initListsForFields();

        if ($this->product->exists) {
            $this->editing = true;

            $this->product->price = number_format($this->product->price / 100, 2);

            $this->categories = $this->product->categories()->pluck('id')->toArray();
        }
    }

    public function save(): RedirectResponse|Redirector
    {
        $this->validate();

        $this->product->price = $this->product->price * 100;

        $this->product->save();

        $this->product->categories()->sync($this->categories);

        return redirect()->route('products.index');
    }

    public function render(): View
    {
        return view('livewire.product-form');
    }

    protected function rules(): array
    {
        return [
            'product.name' => ['required', 'string'],
            'product.description' => ['required'],
            'product.country_id' => ['required', 'integer', 'exists:countries,id'],
            'product.price' => ['required'],
            'categories' => ['required', 'array']
        ];
    }

    //Now let's add those properties and initialize the countries and categories list.
    protected function initListsForFields(): void
    {
        $this->listsForFields['countries'] = Country::pluck('name', 'id')->toArray();

        $this->listsForFields['categories'] = Category::where('is_active', true)->pluck('name', 'id')->toArray();
    }
}
