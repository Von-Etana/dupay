
 @foreach ($column as $item)
 @if(isset($item->create) && $item->create == false)

 @elseif((isset($item->edit) && $item->edit == false) && isset($data))


@else
 @php
 $n = $item->column_name;
 $id = $item->id ?? "";
@endphp
 @switch($item->input_type)
     @case("1")
     {!! getTextFormInput("password", $item->name, $item->column_name, $item->value, $item->description, isset($data) ? "" : $item->required ) !!}
         @break
     @case("2")
     {!! getTextFormInput("email", $item->name, $item->column_name, isset($data) ? $data->$n : $item->value,$item->description, $item->required ) !!}
     @break
     @case("3")
     {!! getTextFormInput("textarea",$item->name, $item->column_name, isset($data) ? $data->$n : $item->value,$item->description, $item->required, "","","","","", "", "", $item->id ?? "", $item->wysiwyg ?? "" ) !!}
     @break
     @case("4")
     {{ getOtherFormInput("selectId", $item->name, $item->column_name, isset($data) ? $data->$n : $item->value, $item->option ?? [], $item->required, $item->ajaxClass ?? "", "", "", "", "", "", $item->dataColumn ?? "", $item->id ?? "") }}
     @break
     @case("4-2")
     {{ getOtherFormInput("selectMultiple", $item->name, $item->column_name, isset($data) ? $data->$id : $item->value, $item->option ?? [], $item->required, $item->ajaxClass ?? "", "", "", "", "", "", $item->dataColumn ?? "", $item->multiple ?? "", $item->id ?? "") }}
     @break
     @case("5")
     {{ getOtherFormInput("checkToggle",$item->name, $item->column_name, isset($data) ? $data->$n : $item->value, $item->option ?? $item->input_option, $item->required) }}
     @break
     @case("7")
     
     
 @php
    $imagePath = isset($data) && !empty($data->$n)
        ? asset('storage/' . $data->$n)
        : null;
@endphp

<div class="mb-3">
    <label for="{{ $item->column_name }}" class="form-label">{{ $item->name }}</label>

    {{-- Show existing image preview --}}
    @if ($imagePath)
        <div class="mb-2">
            <img src="{{ $imagePath }}" alt="Current Image" width="150" style="border-radius: 8px;">
            <a href="{{ $imagePath }}" target="_blank" class="btn btn_danger"> View Image </a>
        </div>
    @endif

    {{-- File input (can upload new image) --}}
    <input type="file" name="{{ $item->column_name }}" id="{{ $item->column_name }}" class="form-control" accept="image/*">

    {{-- Keep old image if user doesn't upload a new one --}}
    @if ($imagePath)
        <input type="hidden" name="existing_{{ $item->column_name }}" value="{{ $data->$n }}">
    @endif

    @if ($item->description)
        <small class="text-muted">{{ $item->description }}</small>
    @endif
</div>

     
     
     @break
     @default
     {!! getTextFormInput("text", $item->name, $item->column_name, isset($data) ? $data->$n : $item->value,$item->description, $item->required, $item->icon ?? "", $item->disabled ?? "", $item->class ?? "",$item->inputClass ?? "",$item->labelClass ?? "", $item->dataColumn ?? "", isset($item->addMore) ? $item->addMore : "" ) !!}
 @endswitch
 @endif
 @endforeach



