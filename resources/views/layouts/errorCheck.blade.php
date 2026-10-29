@if (session()->has('message'))

<div class="alert alert-light-primary   fade show border-0 mb-4" role="alert">
    <strong>Hello!</strong>  {{ session()->get('message') }}</button>
</div>

        @endif
        @if($errors->any())
        @foreach ($errors->all() as $error)
        <div class="alert alert-light-danger alert-dismissible fade show border-0 mb-4" role="alert">
            <strong>Oops</strong> - {!!  $error !!}</button>
        </div>

        @endforeach
        @endif
