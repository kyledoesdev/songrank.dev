# Validation & Forms Best Practices

## Validation Lives in Livewire Form Objects

**This application has no Form Request classes, and `app/Http/Requests/` does not exist.** Input arrives through Livewire, so validation lives in a form object in `app/Livewire/Forms/` using `#[Validate]` attributes:

```php
class TierlistForm extends Form
{
    /** An unnamed list takes the name of its source, or of its type. */
    #[Validate('string|nullable|max:30')]
    public string $name = '';

    #[Validate('required')]
    public $is_public = true;

    public function updatedCommentsEnabled($value): void
    {
        if (! $value || $value === '0') {
            $this->comments_replies_enabled = '0';
        }
    }
}
```

- **String notation** for rules, matching `RankingForm` and `TierlistForm`
- The component holds it as a typed public property (`public TierlistForm $form;`), usually through a concern (`HasRankingForm`, `HasTierlistForm`)
- `updatedX()` hooks on the form keep dependent fields coherent — switching comments off switches replies off too
- Resetting is explicit: `$this->reset(['form.name', 'form.is_public', ...])`

Add a Form Request only if a real controller ever needs one, and follow the array-notation guidance below when that happens.

## Not Every Guard Is Validation

Two patterns in this codebase deliberately sit outside the validator:

- **Allowances.** Whether a user may create another ranking or tier list is answered by `canCreateRanking()` / `canCreateTierlist()`, surfaced as a UI block in `setup-header.blade.php`, and backstopped by `ensureCanCreateRanking()` / `ensureCanCreateTierlist()` at the top of `search()`. It is not a validation rule and not a policy — a 403 after clicking "begin ranking" is the wrong place to find out.
- **Domain preconditions.** "A board needs two entries before sorting them into tiers means anything" is a check in `startTierlist()` that flashes a message, not a rule on a field.

Both flash through `Livewire/Concerns/InteractsWithAlerts` rather than adding an error bag entry.

## If You Do Add a Form Request

### Array vs. String Notation

Array syntax composes cleanly with `Rule::` objects and is preferred for new Form Requests. The Livewire forms above use string notation — match whatever the file you are editing already does.

```php
'email' => ['required', 'email', Rule::unique('users')],
```

### Always Use `validated()`

```php
// Wrong
Post::create($request->all());

// Right
Post::create($request->validated());
```

### Use `Rule::when()` for Conditional Validation

```php
'company_name' => [
    Rule::when($this->account_type === 'business', ['required', 'string', 'max:255']),
],
```

### Use `after()` for Cross-Field Rules

Prefer `after()` over `withValidator()` when the check depends on more than one field.

```php
public function after(): array
{
    return [
        function (Validator $validator) {
            if ($this->quantity > Product::find($this->product_id)?->stock) {
                $validator->errors()->add('quantity', 'Not enough stock.');
            }
        },
    ];
}
```
