@if ($type == "textarea")
<div class="mb-3">
    <label for="{{ $column_name }}" class="label">{{ $name }}</label>
    @if(isset($wysiwyg) && $wysiwyg == true)
    <div id="{{ $id }}">
       {!! $value !!}
    </div>
    <input type="hidden"  name="{{ $column_name }}" id="{{ $column_name }}" value="{{ old("$column_name") ?? $value }}">
    @else
    <textarea name="{{ $column_name }}"
    id="{{ $id ?? $column_name }}"
    class="form-control"
    placeholder="{{ $description }}"
    cols="30" rows="10"{{ $required }} {{ $disabled }}>{{ old("$column_name") ?? $value }}</textarea>
    @endif
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

{{--  check if its addmore  --}}
@if ($addMore != null && $addMore->available == true)
{{--  check if value is not empty  --}}
@if ($value != null)
    @php
    $addMoreData = json_decode($value, true);
    $addMoreDataCount = 1;
    @endphp

    @if ($addMoreData != null &&  is_array($addMoreData))
        @foreach ($addMoreData as $addMoreDatakey => $addMoreDatavalue)
        <div class="row">
            <div class=" @if($addMore->hasValue) col-md-6 @else col-md-12 @endif ">
                <div class="mb-3">
                    <label for="{{ $column_name }}" class="label">{{ $name }}</label>

                    @if (!$addMore->hasValue)
                    @if ($addMoreDataCount == 1)
                    <a href="javascript:void(0)" class="badge outline-badge-success mb-2 me-4 addMore float-end" data-count="1" data-name="{{ $name }}" data-column="{{ $column_name }}" data-description="{{ $description }}" data-hasvalue="{{ $addMore->hasValue }}">Add More</a>
                    @else
                    <a href="javascript:void(0)" class="badge outline-badge-danger  float-end" onclick="removePayloadField(this)" >Remove</a>
                    @endif
                    @endif


                    <input type="{{ $type }}" name="{{ $column_name }}[]" id="{{ $column_name }}" class="@if($inputClass != null){{ $inputClass }}@endif form-control @error("$column_name")  is-invalid @enderror" value="{{ old("$column_name") ?? ($addMore->hasValue ?  $addMoreDatakey : $addMoreDatavalue) }}" placeholder="{{ $description }}" {{ $required }} {{ $disabled }}>

                    @error("$column_name")
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
                </div>
            </div>

            @if ($addMore->hasValue)
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="{{ $column_name }}_value" class="label">{{ $name }} value</label>
                    @if ($addMoreDataCount == 1)
                    <a href="javascript:void(0)" class="badge outline-badge-success addMore float-end" data-count="1" data-name="{{ $name }}" data-column="{{ $column_name }}" data-description="{{ $description }}" data-hasvalue="{{ $addMore->hasValue }}">Add More</a>
                    @else
                    <a href="javascript:void(0)" class="badge outline-badge-danger  float-end" onclick="removePayloadField(this)" >Remove</a>
                    @endif

                    <input type="{{ $type }}" name="{{ $column_name }}_value[]" id="{{ $column_name }}_value" class="@if($inputClass != null){{ $inputClass }}@endif form-control @error("$column_name")  is-invalid @enderror" value="{{ old("$column_name.'_value'") ?? $addMoreDatavalue }}" placeholder="{{ $description }} value" {{ $required }} {{ $disabled }}>

                    @error("$column_name.'_value'")
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
                </div>
            </div>
            @endif


                </div>
                @php
                $addMoreDataCount++;
                @endphp
        @endforeach

    @endif
@else
{{--  value is empty  --}}
<div class="row">
    <div class=" @if($addMore->hasValue) col-md-6 @else col-md-12 @endif ">
        <div class="mb-3">
            <label for="{{ $column_name }}" class="label">{{ $name }}</label>

            @if (!$addMore->hasValue)
            <a href="javascript:void(0)" class="badge outline-badge-success mb-2 me-4 addMore float-end" data-count="1" data-name="{{ $name }}" data-column="{{ $column_name }}" data-description="{{ $description }}" data-hasvalue="{{ $addMore->hasValue }}">Add More</a>
            @endif


            <input type="{{ $type }}" name="{{ $column_name }}[]" id="{{ $column_name }}" class="@if($inputClass != null){{ $inputClass }}@endif form-control @error("$column_name")  is-invalid @enderror" value="{{ old("$column_name") ?? $value }}" placeholder="{{ $description }}" {{ $required }} {{ $disabled }}>

            @error("$column_name")
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror
        </div>
    </div>

    @if ($addMore->hasValue)
    <div class="col-md-6">
        <div class="mb-3">
            <label for="{{ $column_name }}_value" class="label">{{ $name }} value</label>
            <a href="javascript:void(0)" class="badge outline-badge-success addMore float-end" data-count="1" data-name="{{ $name }}" data-column="{{ $column_name }}" data-description="{{ $description }}" data-hasvalue="{{ $addMore->hasValue }}">Add More</a>
            <input type="{{ $type }}" name="{{ $column_name }}_value[]" id="{{ $column_name }}_value" class="@if($inputClass != null){{ $inputClass }}@endif form-control @error("$column_name")  is-invalid @enderror" value="{{ old("$column_name.'_value'") ?? $value }}" placeholder="{{ $description }} value" {{ $required }} {{ $disabled }}>

            @error("$column_name.'_value'")
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror
        </div>
    </div>
    @endif


        </div>

@endif

    <div id="{{ $column_name }}Fields">
</div>
@else
    {{--  its not add more  --}}
<div class="form-custom form-label mt-3">
    <label for="{{ $column_name }}" class="label">{{ $name }}</label>

    <input type="{{ $type }}" name="{{ $column_name }}" id="{{ $column_name }}" class="@if($inputClass != null){{ $inputClass }}@endif form-control rounded-xs @error("$column_name")  is-invalid @enderror" value="{{ old("$column_name") ?? $value }}" placeholder="{{ $description }}" {{ $required }} {{ $disabled }}>

    @error("$column_name")
    <span class="invalid-feedback" role="alert">
        <strong>{{ $message }}</strong>
    </span>
@enderror
</div>
@endif
{{--  end of check if its add more  --}}


@endif


@if ($disabled != null && $value != null)
  <input type="hidden" name="{{ $column_name }}"  value="{{ $value }}">
@endif



