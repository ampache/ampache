{{-- \resources\views\permissions\index.blade.php --}}
@extends('layouts.app')

@section('title', '| Permissions')

@section('content')
<div id="main-content" class="w3-container w3-section" style="margin-left:14%">
 <div class="w3-display-container w3-black" style="height:500px;">
 <div class="w3-display-middle" style="width:400px">

    <h4><i class="fa fa-key"></i>Available Permissions</h4>
    <hr>
        <table class="w3-bordered w3-small">

            <thead>
      {{-- \resources\views\permissions\index.blade.php --}}
                <tr>
                    <th>Permissions</th>
                    <th>Operation</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($permissions as $permission)
                <tr>
                    <td>{{ $permission->name }}</td> 
                    <td>
                    <a href="{{ URL::to('permissions/'.$permission->id.'/edit') }}" class="w3-right" style="margin-right: 3px;">Edit</a>
					</td>
					<td>
                    {!! Form::open(['method' => 'DELETE', 'route' => ['permissions.destroy', $permission->id] ]) !!}
                    {!! Form::submit('Delete', ['class' => 'btn btn-danger']) !!}
                    {!! Form::close() !!}

                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

    <a href="{{ URL::to('permissions/create') }}" class="btn btn-success">Add Permission</a>

</div>
</div>
</div>

@endsection