@if ($type == "radio")
<fieldset class="mb-3">
    <div class="row">
        <label class="col-form-label col-sm-3 pt-0">{{ $name }}</label>
        <div class="col-sm-9">
            @foreach ($option as $item)
            @if ($item == $value)
            <div class="form-check">
                <input class="form-check-input" type="radio" name="{{ $column_name }}" value="{{ $item }}" checked>
                <label class="form-check-label">
                    {{ $item }}
                </label>
            </div>
           @else
            <div class="form-check">
                <input class="form-check-input" type="radio" name="{{ $column_name }}" value="{{ $item }}">
                <label class="form-check-label">
                    {{ $item }}
                </label>
            </div>
            @endif
            @endforeach

        </div>
    </div>
</fieldset>
@endif

@if ($type == "checkToggle" && $option != null)
@if (auth()->guard("admin")->check())
<div class="row -mb2">
    <label class="col-form-label col-md-3">{{ $name }}</label>
    @php

        $options = explode(",", $option);

    @endphp
    <div class="col-md-9">

        <div class="switch form-switch-custom switch-inline form-switch-primary form-switch-custom inner-label-toggle {{ $options[0] == $value ? 'show' : '' }}">
            <div class="dw-switch-wrap">
                <span class="switch-chk-label label-left">{{ $options[1] }}</span>
                <input class="switch-input {{ $ajaxClass ? $ajaxClass : "" }}" name="{{ $column_name }}" id="{{ $column_name }}" type="checkbox" role="switch"  onchange="this.checked ? this.closest('.inner-label-toggle').classList.add('show') : this.closest('.inner-label-toggle').classList.remove('show')" {{ $options[0] == $value ? "checked" : "" }}>
                <span class="switch-chk-label label-right">{{ $options[0] }}</span>
            </div>
        </div>
</div>
</div>
@else
<fieldset class="mb-3">
    <div class="row">
        <label class="col-form-label col-md-3 pt-0">{{ $name }}</label>
        @php
            $options = explode(",", $option);
        @endphp
        <div class="col-md-9">
<input type="checkbox" name="{{ $column_name }}" id="{{ $column_name }}" data-width="100" data-size="sm" data-toggle="switchbutton" data-onlabel="{{ $options[0] }}" data-offlabel="{{ $options[1] }}" data-onstyle="success" data-offstyle="danger" {{ $options[0] == $value ? "checked" : "" }}>
</div>
</div>
</fieldset>
@endif
@endif

{{--  @if ($type == "checkToggle" && $option != null)

    <div class="row -mb2">
        <label class="col-form-label col-md-3">{{ $name }}</label>
        @php

            $options = explode(",", $option);

        @endphp
        <div class="col-md-9">

            <div class="switch form-switch-custom switch-inline form-switch-primary form-switch-custom inner-label-toggle {{ $options[0] == $value ? 'show' : '' }}">
                <div class="dw-switch-wrap">
                    <span class="switch-chk-label label-left">{{ $options[1] }}</span>
                    <input class="switch-input {{ $ajaxClass ? $ajaxClass : "" }}" name="{{ $column_name }}" id="{{ $column_name }}" type="checkbox" role="switch"  onchange="this.checked ? this.closest('.inner-label-toggle').classList.add('show') : this.closest('.inner-label-toggle').classList.remove('show')" {{ $options[0] == $value ? "checked" : "" }}>
                    <span class="switch-chk-label label-right">{{ $options[0] }}</span>
                </div>
            </div>
</div>
</div>
@endif  --}}


{{--  @if ($type == "checkToggle")
<fieldset class="mb-3">
    <div class="row">
        <label class="col-form-label col-md-3 pt-0">{{ $name }}</label>
        @php
            $options = explode(",", $option);
        @endphp
        <div class="col-md-9">
<input type="checkbox" name="{{ $column_name }}" id="{{ $column_name }}" data-width="100" data-size="sm" data-toggle="switchbutton" data-onlabel="{{ $options[0] }}" data-offlabel="{{ $options[1] }}" data-onstyle="success" data-offstyle="danger" {{ $options[0] == $value ? "checked" : "" }}>
</div>
</div>
</fieldset>
@endif  --}}


@if ($type == "radioId")
<fieldset class="mb-3">
    <div class="row">
        <label class="col-form-label col-sm-3 pt-0">{{ $name }}</label>
        <div class="col-sm-9">
            @foreach ($option as $item)
            @if ($item->id == $value)
            <div class="form-check">
                <input class="form-check-input" type="radio" name="{{ $column_name }}" value="{{ $item->id }}" checked>
                <label class="form-check-label">
                    {{ $item->name }}
                </label>
            </div>
            @else
            <div class="form-check">
                <input class="form-check-input" type="radio" name="{{ $column_name }}" value="{{ $item->id }}">
                <label class="form-check-label">
                    {{ $item->id }}
                </label>
            </div>
            @endif
            @endforeach

        </div>
    </div>
</fieldset>
@endif

@if ($type == "select")
<div class="@if (isMobile())
form-custom form-label @if ($icon != null) form-icon @endif mt-3 @else mb-3 @endif ">
    <label for="{{ $column_name }}" class="@if(isMobile())form-label-always-active color-highlight font-11 @endif">{{ $name }}</label>
    <select name="{{ $column_name }}" class="form-control input-default {{ $ajaxClass ? $ajaxClass : "" }}" id="{{ $column_name }}" {{ $required }}>
        <option value="" selected>Please Select</option>
        @foreach ($option as $item)
        @if ($item == $value)
        <option value="{{$item}}" selected>{{$item}}</option>
        @endif
        @if ($item != $value)
        <option value="{{$item}}">{{$item}}</option>
        @endif
        @endforeach
    </select>
</div>
@endif



@if ($type == "selectId")
@php
    $v = "";
@endphp
<div class="@if (isMobile())
form-custom form-label @if ($icon != null) form-icon @endif mt-3 @else mb-3 @endif
 ">
 @if (isMobile())
 @if ($icon != null)
 <i class="bi bi-{{ $icon }} font-14"></i>
 @endif
 @endif
 <label for="{{ $column_name }}" class="@if(isMobile())form-label-always-active color-highlight font-11 @endif">{{ $name }}</label>
    <select name="{{ $column_name }}" class="@if (isMobile()) form-select rounded-xs @else form-control input-default @endif {{ $ajaxClass ? $ajaxClass : "" }}" id="{{  $column_name }}" {{ $required }} {{ $disabled }}>
        <option value="" selected>Please Select</option>

        @if ($option != null)
        @foreach ($option as $item)
        <!-- data column is not null -->
        @if ($dataColumn != null)
        @php
            $id = $dataColumn->id;
            $name = $dataColumn->name;
        @endphp
        @if ($item->$id == $value)
        @php
            $v = $item->$id;
        @endphp
        <option value="{{$item->$id}}" selected>{{$item->$name}}</option>
        @endif
        @if ($item->id != $value)
        <option value="{{$item->$id}}">{{$item->$name}}</option>
        @endif
        <!-- end of data column not null-->
        @else
        <!-- if auto get data column with id and name-->
        @if ($item->id == $value)
        @php
            $v = $item->id;
        @endphp
        <option value="{{$item->id}}" selected>{{$item->name}}</option>
        @endif
        @if ($item->id != $value)
        <option value="{{$item->id}}">{{$item->name}}</option>
        @endif
        <!-- end of auto get data column with id and name-->
        @endif

        @endforeach
        @endif
    </select>
    @if ($disabled == "disabled")
        <input type="hidden" name="{{ $column_name }}" id="{{ $id ?? $column_name }}" value="{{ $v }}"/>
    @endif
</div>
@endif



@if ($type == "selectJson")
@php
    $v = "";
@endphp
<div class="@if (isMobile())
form-custom form-label  @if ($icon != null) form-icon @endif mt-3 @else mb-3 @endif ">
 @if (isMobile())
 @if ($icon != null)
 <i class="bi bi-{{ $icon }} font-14"></i>
 @endif
 @endif
    <label for="{{ $column_name }}" class="@if(isMobile())form-label-always-active color-highlight font-11 @endif">{{ $name }}</label>
    <select name="{{ $column_name }}" class="@if (isMobile()) form-select rounded-xs @else form-control input-default @endif {{ $ajaxClass ? $ajaxClass : "" }}" id="{{ $column_name }}" {{ $required }} {{ $disabled }} aria-label="Floating label select example">
        <option value="" selected>Please Select</option>
        @if ($option != null)
        @foreach ($option as $item)
        @php
            $info = [
                'id' => $item->id,
                'name' => $item->name,
                "image" => $item->image
              ];
            $info = json_encode($info);
        @endphp
        @if ($item->id == $value)
        @php
            $v = $item->id;
        @endphp
        <option value="{{$info}}" selected>{{$item->name}}</option>
        @endif
        @if ($item->id != $value)
        <option value="{{$info}}">{{$item->name}}</option>
        @endif
        @endforeach
        @endif
    </select>
    @if ($disabled == "disabled")
        <input type="hidden" name="{{ $column_name }}" value="{{ $v }}"/>
    @endif
</div>
@endif



@if ($type == "selectMultiple")
@php
    $v = "";
@endphp
<div class="@if (isMobile())
form-custom form-label  @if ($icon != null) form-icon @endif mt-3 @else mb-3 @endif ">
 @if (isMobile())
 @if ($icon != null)
 <i class="bi bi-{{ $icon }} font-14"></i>
 @endif
 @endif
    <label for="{{ $column_name }}" class="@if(isMobile())form-label-always-active color-highlight font-11 @endif">{{ $name }}</label>
    <select aria-placeholder="Please Select" name="{{ $column_name }}[]" class="@if (isMobile()) form-select rounded-xs @else form-control input-default @endif {{ $ajaxClass ? $ajaxClass : "" }} select2" id="{{ $column_name }}" {{ $required }} {{ $disabled }} aria-label="Floating label select example" multiple>
        @if ($option != null)
        @php
       $mId = $multiple->id;
       $mName = $multiple->name;
       $aValue = [];

       if(isset($value) && $value != ""){
            foreach($value as $mItem){
                array_push($aValue, $mItem->$mId);
            }
       }
        @endphp

        @foreach ($option as $item)
        @if (in_array($item->id, $aValue))
        <option value="{{$item->id}}" selected>{{$item->name}}</option>
        @else
        <option value="{{$item->id}}">{{$item->name}}</option>
        @endif
        @endforeach
        @endif
    </select>
    @if ($disabled == "disabled")
        <input type="hidden" name="{{ $column_name }}" value="{{ $v }}"/>
    @endif
</div>
@endif


