<div>
    <label for="{{ $name }}">{{ $label }}</label>
    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        value="{{ old($name, $value) }}"
        class="{{ $inputClass() }}"
        {{ $attributes }}
    >
    @error($name)
        <p style="color: red;">{{ $message }}</p>
    @enderror
</div>
