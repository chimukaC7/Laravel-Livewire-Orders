<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Category;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use Illuminate\Support\Collection;
use Illuminate\Contracts\View\View;

class CategoriesList extends Component
{
    use WithPagination;

    public Category $category;

    public Collection $categories;

    public bool $showModal = false;

    public array $active;

    public int $editedCategoryId = 0;

    public int $currentPage = 1;

    public int $perPage = 10;

    protected $listeners = ['delete'];

    public function openModal()
    {
        $this->showModal = true;

        $this->category = new Category();
    }

    public function updatedCategoryName()
    {
        $this->category->slug = Str::slug($this->category->name);
    }

    public function save()
    {
        $this->validate();

        if ($this->editedCategoryId === 0) {
            $this->category->position = Category::max('position') + 1;
        }

        $this->category->save();

        $this->resetValidation();
        $this->reset('showModal', 'editedCategoryId');
    }

    public function cancelCategoryEdit()
    {
        $this->resetValidation();
        $this->reset('editedCategoryId');
    }

    public function toggleIsActive($categoryId)
    {
        Category::where('id', $categoryId)
            ->update(
                ['is_active' => $this->active[$categoryId]]
            );
    }

    public function updateOrder($list)
    {
        foreach ($list as $item) {
            $cat = $this->categories->firstWhere('id', $item['value']);
            $order = $item['order'] + (($this->currentPage - 1) * $this->perPage);

            if ($cat['position'] != $order) {
                Category::where('id', $item['value'])->update(['position' => $order]);
            }
        }
    }

    public function editCategory($categoryId)
    {
        $this->editedCategoryId = $categoryId;

        $this->category = Category::find($categoryId);
    }

    public function deleteConfirm($method, $id = null)
    {
        $this->dispatchBrowserEvent('swal:confirm', [
            'type' => 'warning',
            'title' => 'Are you sure?',
            'text' => '',
            'id' => $id,
            'method' => $method,
        ]);
    }

    public function delete($id)
    {
        Category::findOrFail($id)->delete();
    }

    public function render(): View
    {
        $categories = Category::orderBy('position')->paginate($this->perPage);
        $links = $categories->links();
        $this->currentPage = $categories->currentPage();
        $this->categories = collect($categories->items());

        //Categories migration we have a column is_active, so we will change its value in the database.
        //Next, we need a list of active categories. For this, we will use a Collections method mapWithKeys().
        $this->active = $this->categories->mapWithKeys(
            fn(Category $item) => [$item['id'] => (bool)$item['is_active']]
        )->toArray();

        return view('livewire.categories-list', ['links' => $links,]);
    }

    protected function rules(): array
    {
        return [
            'category.name' => ['required', 'string', 'min:3'],
            'category.slug' => ['nullable', 'string'],
        ];
    }
}
