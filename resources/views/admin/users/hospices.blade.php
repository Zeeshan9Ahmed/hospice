@extends('admin.layout.master')
@section('style')
<link rel="stylesheet" href="{{asset('public/admin/plugins/fontawesome-free/css/all.min.css')}}">
  <!-- DataTables -->
  <link rel="stylesheet" href="{{asset('public/admin/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css')}}">
  <link rel="stylesheet" href="{{asset('public/admin/plugins/datatables-responsive/css/responsive.bootstrap4.min.css')}}">
  <link rel="stylesheet" href="{{asset('public/admin/plugins/datatables-buttons/css/buttons.bootstrap4.min.css')}}">
  <!-- Theme style -->
  <link rel="stylesheet" href="{{asset('public/admin/dist/css/adminlte.min.css')}}">
@endsection
@section('title','Hospices')
@section('content')
 <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Users</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Users</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">

          @if(session('success'))
                <div class="sufee-alert alert with-close alert-success alert-dismissible fade show">
                    <span class="badge badge-pill badge-success"></span>
                        {{session('success')}}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
            @endif

            @if($errors->any())
                <div class="sufee-alert alert with-close alert-danger alert-dismissible fade show">
                    <span class="badge badge-pill badge-danger"></span>
                        <h4>{{$errors->first()}}</h4>
                    <!-- <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button> -->
                </div>
            @endif

            <div class="card">
              <div class="card-body">
                <table id="example1" class="table table-bordered table-striped">

                  <thead>
                      <tr>
                        <th>ID</th>

                        <th>Business Name</th>

                        <th>Email Address</th>

                        <th>Approve</th>

                        <th>Action</th>
                      </tr>
                  </thead>

                  <tbody>

                       @forelse($hospices as $key=> $user)

                        <tr>

                            <td>{{$user->id}}</td>
                             <!-- <td><img src="{{asset('public/web/assets/images/loginLogo.png')}}" alt="demo" width="50px" height="50px"></td> -->
                            <td>
                                {{$user->business_name}}
                            </td>
                            <td>
                                {{$user->email}}
                            </td>
                            <td>
                                @if ($user->is_approved == 0)
                                    <button class="btn btn-danger approve" data-id="{{$user->id}}" data-type="approve"><i class="fa fa-check"></i></button><span class="pl-1">Approve</span>
                                @else
                                    <button class="btn btn-dark unapprove" data-id="{{$user->id}}" data-type="unapprove"><i class="fa fa-times"></i></button><span class="pl-1">UnApprove</span>
                                @endif
                            </td>
                            <td>
                                @if ($user->is_blocked == 0)
                                    <button class="btn btn-danger block" data-id="{{$user->id}}" data-type="block"><i class="fa fa-ban"></i></button><span class="pl-1">Block</span>
                                @else
                                    <button class="btn btn-dark unblock" data-id="{{$user->id}}" data-type="unblock"><i class="fa fa-ban"></i></button><span class="pl-1">Unblock</span>
                                @endif
                            </td>

                        </tr>

                        @empty

                      @endforelse

                 </tbody>
                </table>
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
      </div>
      <!-- /.container-fluid -->
    </section>
@endsection
@section('script')
{{--    @include('sweet::alert')--}}
<!-- Bootstrap 4 -->
<!-- DataTables  & Plugins -->
<script src="{{asset('public/admin/plugins/datatables/jquery.dataTables.min.js')}}"></script>
<script src="{{asset('public/admin/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js')}}"></script>
<script src="{{asset('public/admin/plugins/datatables-responsive/js/dataTables.responsive.min.js')}}"></script>
<script src="{{asset('public/admin/plugins/datatables-responsive/js/responsive.bootstrap4.min.js')}}"></script>
<script src="{{asset('public/admin/plugins/datatables-buttons/js/dataTables.buttons.min.js')}}"></script>
<script src="{{asset('public/admin/plugins/datatables-buttons/js/buttons.bootstrap4.min.js')}}"></script>
<script src="{{asset('public/admin/plugins/jszip/jszip.min.js')}}"></script>
<script src="{{asset('public/admin/plugins/pdfmake/pdfmake.min.js')}}"></script>
<script src="{{asset('public/admin/plugins/pdfmake/vfs_fonts.js')}}"></script>
<script src="{{asset('public/admin/plugins/datatables-buttons/js/buttons.html5.min.js')}}"></script>
<script src="{{asset('public/admin/plugins/datatables-buttons/js/buttons.print.min.js')}}"></script>
<script src="{{asset('public/admin/plugins/datatables-buttons/js/buttons.colVis.min.js')}}"></script>
<script>
  $(function () {

    $("#example1").DataTable({
      "responsive": true, "lengthChange": false, "autoWidth": false,
      // "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
    }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');

    $('#example2').DataTable({
      "paging": true,
      "lengthChange": false,
      "searching": false,
      "ordering": true,
      "info": true,
      "autoWidth": false,
      "responsive": true,
    });

  });

   $(document).on("click",".block",function(e) {
       e.preventDefault();
       if (!confirm("This user will be blocked")) {
           return false;
       }
       var id = $(this).data("id");
       var type = $(this).data("type");
       $.ajax({
           type: 'post',
           url: '{{ route('admin.users.block') }}',
           data: {
               id: id,
               user_type: type,
               _method: 'GET',
               _token: '{{csrf_token()}}',
           },
           success: function (res) {
               location.reload();
           }
       })
   });

  $(document).on("click",".unblock",function(e) {
      e.preventDefault();
      if (!confirm("This user will be unblocked")) {
          return false;
      }
      var id = $(this).data("id");
      var type = $(this).data("type");
      $.ajax({
          type: 'post',
          url: '{{ route('admin.users.block') }}',
          data: {
              id: id,
              user_type: type,
              _method: 'GET',
              _token: '{{csrf_token()}}',
          },
          success: function (res) {
              console.log(res);
              location.reload();
          }
      })
  });

  $(document).on("click",".approve",function(e) {
      e.preventDefault();
      if (!confirm("This user will be approved")) {
          return false;
      }
      var id = $(this).data("id");
      var type = $(this).data("type");
      $.ajax({
          type: 'post',
          url: '{{ route('admin.users.approve') }}',
          data: {
              id: id,
              user_type: type,
              _method: 'GET',
              _token: '{{csrf_token()}}',
          },
          success: function (res) {
              location.reload();
          }
      })
  });

  $(document).on("click",".unapprove",function(e) {
      e.preventDefault();
      if (!confirm("This user will be unapproved")) {
          return false;
      }
      var id = $(this).data("id");
      var type = $(this).data("type");
      $.ajax({
          type: 'post',
          url: '{{ route('admin.users.approve') }}',
          data: {
              id: id,
              user_type: type,
              _method: 'GET',
              _token: '{{csrf_token()}}',
          },
          success: function (res) {
              console.log(res);
              location.reload();
          }
      })
  });
</script>
@endsection
