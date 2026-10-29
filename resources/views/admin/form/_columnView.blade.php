@php
$n = $column->column_name;
@endphp

@if (isset($column->model))
    @php
        $reponseVal = $item;
        foreach (explode('->', $column->model) as $itemS) {
            if (!str_contains($itemS, '()')) {
                $reponseVal = $reponseVal->{$itemS} ?? "";
            } else {
                $itemS = str_replace('()', '', $itemS);
                $reponseVal = $reponseVal->{$itemS}() ?? "";
            }
        }
        $modeData = $reponseVal ?? '-';
    @endphp
@endif

@if ($column->column_name == 'created_at')
{{ \Carbon\Carbon::parse($item->$n)->format('d M Y h:i A') }}
@elseif ($column->input_type == '7')

    @if (isset($modeData))
        <div class="d-flex align-items-center">
            <div class="avatar">
            <img src="{{ url('/') }}/storage/{{ $modeData }}"  width="64" height="64"
                alt=""></div>
            </div>
    @else
        <div class="d-flex align-items-center">
            <div class="avatar">
            <img src="{{ url('/') }}/storage/{{ $item->$n }}"  width="64" height="64"
                alt=""></div>
        </div>
    @endif


@elseif($column->input_type == '4' && $column->column_name == 'status_id' || $column->column_name == 'status' && isset($modeData))

            @php
                $statusItem = App\Models\Status::where('name', $modeData)->orWhere("id",$modeData)->orWhere('name', $item->$n)->orWhere("id",$item->$n)->first();
            @endphp
            @if ($statusItem)
            <span class="{{ $statusItem->label }}">{{ $statusItem->name }}</span>
            @else
            {{ $modeData ?? $item->$n}}
            @endif
@elseif($column->input_type == '4' && isset($column->option))

@if (isset($column->use_switch) && $column->use_switch == true)
<div class="switch form-switch-custom switch-inline form-switch-success">
    <input class="switch-input statusTypeUpdate" name="{{ $column->column_name }}" value="{{$item->$n}}" data-url="{{ route('admin.UpdateItemApi',[$pageType, $item->id]) }}" data-column="{{ $column->column_name }}" data-id="{{ $column->column_name }}"
    id="{{ $column->column_name }}" @if (isset($column->model))
    data-model="{{ $column->model }}"
    @endif type="checkbox" role="switch" id="form-custom-switch-primary" @if ($item->$n == '1')
        @checked(true)
    @endif>
</div>
@else

{{--  check if datacolumn is not null  --}}
@php
$dataColumn = $column->dataColumn ?? null;
@endphp
@if ($dataColumn != null)
@php
    $id = $dataColumn->id;
    $name = $dataColumn->name;

    $valueColumn = isset($dataColumn->value_column) ? $dataColumn->value_column : null;
@endphp
@foreach ($column->option as $optionValue)
    @if ($valueColumn != null)
        @if ($optionValue->$id == $item->$valueColumn)
            {{ $optionValue->$name }}
        @endif
    @else
        @if ($optionValue->$id == $item->$n)
            {{ $optionValue->$name }}
        @endif
    @endif
@endforeach
{{--  end check if datacolumn is not null  --}}
@else

@foreach ($column->option as $optionValue)
@if ($optionValue->id == $item->$n)
    @if ($optionValue->id == '0')
        <span class="badge badge-danger mb-2 me-4">{{ $optionValue->name ?? "" }}</span>
    @else
        <span class="badge badge-success mb-2 me-4">{{ $optionValue->name ?? "" }}</span>
    @endif
@endif

@endforeach
@endif

@endif


@elseif($column->input_type == '4-2')
    @if ($column->option != null)
        @php
            $mId = $column->multiple->id;
            $mName = $column->multiple->name;
            $aValue = [];

            if (isset($item->$n) && $item->$n != '') {
                foreach ($item->$n as $mItem) {
                    array_push($aValue, $mItem->$mId);
                }
            }
        @endphp
        @foreach ($column->option as $optionValue)
            @if (in_array($optionValue->id, $aValue))
                {{ $optionValue->$mName }}
            @endif
        @endforeach
    @endif
@elseif(isset($column->option))
    @php
        $dataColumn = $column->dataColumn ?? null;
    @endphp
    @if ($dataColumn != null)
        @php
            $id = $dataColumn->id;
            $name = $dataColumn->name;

            $valueColumn = isset($dataColumn->value_column) ? $dataColumn->value_column : null;
        @endphp
        @foreach ($column->option as $optionValue)
            @if ($valueColumn != null)
                @if ($optionValue->$id == $item->$valueColumn)
                    {{ $optionValue->$name }}
                @endif
            @else
                @if ($optionValue->$id == $item->$n)
                    {{ $optionValue->$name }}
                @endif
            @endif
        @endforeach
    @else
        @foreach ($column->option as $optionValue)
            @if ($optionValue->id == $item->$n)
                {{ $optionValue->name }}
            @endif
        @endforeach
    @endif
@else
    @if (isset($modeData))
    {{--  check inplace editable  --}}
    @if (isset($column->editable) && $column->editable == true)
    <a href="javascript:void(0)" class="iSmartInPlace" id="{{ $column->column_name }}-{{ $modeData }}" data-column="{{ $column->column_name }}"   @if (isset($column->model))
        data-model="{{ $column->model }}" @endif data-type="text" data-id="{{ $item->id }}" data-url="{{ route('admin.UpdateItemApi',[$pageType, $item->id]) }}" data-title="{{ $column->description }}">{{ $modeData ?? " - " }}</a>

    @else
        @if (isset($column->link))
        @php
        $link = $column->link;
        $link = str_replace("@@this@@",$modeData,$link);
        $link = str_replace("@@id@@",$item->id,$link);
        @endphp
            <a href="{{ $link }}">{{ $modeData }}</a>
        @else
        {{ $modeData }}
        @endif
        @endif
    @else
            {{--  check inplace editable  --}}
            @if (isset($column->editable) && $column->editable == true)
            <a href="javascript:void(0)" class="iSmartInPlace" id="{{ $column->column_name }}-{{ $item->$n }}" data-column="{{ $column->column_name }}"   @if (isset($column->model))
                data-model="{{ $column->model }}" @endif data-type="text" data-id="{{ $item->id }}" data-url="{{ route('admin.UpdateItemApi',[$pageType, $item->id]) }}" data-title="{{ $column->description }}">{{ $item->$n ?? "  - " }}</a>

            @else
            {{ $item->$n }}
            @endif

    @endif
@endif
