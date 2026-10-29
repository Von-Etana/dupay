@if ($type == "textarea")
<div class="mb-3">
    <label for="{{ $column_name }}" class="label">{{ $name }}</label>
    <textarea name="{{ $column_name }}"
    id="{{ $column_name }}"
    class="form-control"
    placeholder="{{ $description }}"
    cols="30" rows="10"{{ $required }} {{ $disabled }}>{{ old("$column_name") ?? $value }}</textarea>
    </div>
@elseif ($type == "file")
    @if (isMobile())
    <div class="form-custom form-label mt-3">
        <input type="{{ $type }}" name="{{ $column_name }}" id="{{ $column_name }}" class="form-control rounded-xs @error("$column_name")  is-invalid @enderror" value="{{ old("$column_name") ?? $value }}" placeholder="{{ $description }}" {{ $required }} {{ $disabled }}>
        <label for="{{ $column_name }}" class="color-highlight">{{ $name }}</label>
        @if ($required)
        <span>(required)</span>
        @endif
        </div>
    @else
    <div class="mb-3">
        <label for="{{ $column_name }}" class="label">{{ $name }}</label>
        <input type="{{ $type }}" name="{{ $column_name }}" id="{{ $column_name }}" class="form-file-input form-control @error("$column_name")  is-invalid @enderror" value="{{ old("$column_name") ?? $value }}" placeholder="{{ $description }}" {{ $required }} {{ $disabled }}>
        @error("$column_name")
        <span class="invalid-feedback" role="alert">
            <strong>{{ $message }}</strong>
        </span>
    @enderror
    </div>
    @endif
@else
@if (isMobile())
<!--mobile--->
@if ($class != null)
<div class="{{ $class }}">
@else
<div class="form-custom form-label @if($icon != null) form-icon @endif {{ $class ?? "" }} mt-3">
@endif
    @if ($icon != null)
    <i class="bi bi-{{ $icon }} font-16"></i>
    @endif
    <input type="{{ $type }}" name="{{ $column_name }}" id="{{ $column_name }}" class="@if($inputClass != null){{ $inputClass }}@endif form-control rounded-xs @error("$column_name")  is-invalid @enderror" value="{{ old("$column_name") ?? $value }}" placeholder="{{ $description }}" {{ $required }} {{ $disabled }} autocomplete="off">
    <label for="{{ $column_name }}" class=" @if($labelClass != null) {{ $labelClass }} @else color-highlight @endif">{{ $name }}</label>
    @if ($required)
    <span>(required)</span>
    @endif
    </div>
@else
<div class="mb-3">
    <label for="{{ $column_name }}" class="label">{{ $name }}</label>
    @if ($icon != null)
    <div class="form-icon position-relative">
        <i data-feather="{{ $icon }}" class="fea icon-sm icons"></i>
    <input type="{{ $type }}" name="{{ $column_name }}" id="{{ $column_name }}" class="@if($inputClass != null){{ $inputClass }}@endif form-control ps-5 @error("$column_name")  is-invalid @enderror" value="{{ old("$column_name") ?? $value }}" placeholder="{{ $description }}" {{ $required }} {{ $disabled }}>
    </div>
    @else
    <input type="{{ $type }}" name="{{ $column_name }}" id="{{ $column_name }}" class="@if($inputClass != null){{ $inputClass }}@endif form-control @error("$column_name")  is-invalid @enderror" value="{{ old("$column_name") ?? $value }}" placeholder="{{ $description }}" {{ $required }} {{ $disabled }}>
    @endif
    @error("$column_name")
    <span class="invalid-feedback" role="alert">
        <strong>{{ $message }}</strong>
    </span>
@enderror
</div>
@endif
@endif


@if ($disabled != null && $value != null)
  <input type="hidden" name="{{ $column_name }}"  value="{{ $value }}">
@endif



