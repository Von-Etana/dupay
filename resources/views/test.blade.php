 
@if(session('message'))
    <div >{{ session('message') }}</div>
@endif


<form action="{{ route('testsxx') }}" method="POST">
    @csrf
   
  
  
    <button type="submit" style="width:150px">Send SMS</button>

</form>