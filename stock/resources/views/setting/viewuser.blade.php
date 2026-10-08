@extends('layouts.app')

@section('content')

    <div class="row mx-3 my-2">
      <h2>User List</h2>
      <div class="col"><a class="btn btn-primary float-end" style="color: #fff;" href="{{ url('/setting/createuser')}}">Add User</a></div>
    </div>
    
    <div class="card mb-3">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered" id="dataTables" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th>#</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>User E-mail</th>
                <th>User Role</th>
                <th>Options</th>
              </tr>
            </thead>
            <tbody>
              @if(isset($user)) @foreach($user as $key => $user)
              <tr>
                <td>{{++$key}}</td>
                <td>{{$user->firstname}}</td>
                <td>{{$user->lastname}}</td>
                <td>{{$user->email}}</td>
                <td>{{$user->role}}</td>
                <td class="text-nowrap">
                  <div class="d-inline-flex flex-nowrap align-items-center gap-1">
                  @if(auth()->user()->id == 4 || strtolower(auth()->user()->email ?? '') === 'aaura1177@gmail.com')
                  <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                    <button class="btn btn-info btn-sm" {{($user->id == 1) ? 'style=display:none;' : ''}} data-bs-toggle="modal" data-bs-target="#myEditModalUser{{$user->id}}">
                        <i class="fa fa-edit"></i>
                    </button>
                </span>
              @else
                  <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                      <button class="btn btn-info btn-sm" {{($user->id == 1) ? 'style=display:none;' : ''}} data-bs-toggle="modal" data-bs-target="#myEditModal{{$user->id}}">
                          <i class="fa fa-edit"></i>
                      </button>
                  </span>
                     <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top"
                                                title="Change Role">
                                                <button class="btn btn-info btn-sm"
                                                    {{ $user->id == 1 ? 'style=display:none;' : '' }} data-bs-toggle="modal"
                                                    data-bs-target="#myRoleModal{{ $user->id }}">
<i class="fa fa-user"></i>
                                                </button>
                                            </span>
              @endif

               @if($user->loginSecurity)
                  
                  <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="2FA">
                         <button class="btn btn-info btn-sm" {{($user->id == 1) ? 'style=display:none;' : ''}} data-bs-toggle="modal" data-bs-target="#myEditLogin{{$user->id}}">
                             <i class="fa fa-lock"></i>
                         </button>
                     </span>
                  @endif
              
                  <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete" >
                    <button class="btn btn-danger btn-sm" {{($user->id == '1')?'style=display:none;':''}} data-bs-toggle="modal" data-bs-target="#myModal{{$user->id}}">
                      <i class="fa fa-trash"></i>
                    </button>
                  </span>
                  </div>
                  
                  <!-- MODAL FOR DELETE -->
                  <div class="modal fade" id="myModal{{$user->id}}" role="dialog">
                    <div class="modal-dialog">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h4 class="modal-title">Delete Confirmation</h4>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>  
                        </div>
                        <div class="modal-body">
                          <p>Are You sure you want to Delete this?</p>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
                          <button onclick="user('{{$user->id}}')" class="btn btn-danger">Yes</button>
                        </div>
                      </div>
                    </div>
                  </div>
				  
				  <!-- MODAL FOR EDIT USER DETAILS -->
                  <div class="modal fade" id="myEditModal{{$user->id}}" role="dialog">
                    <div class="modal-dialog">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h4 class="modal-title">Change Password</h4>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>  
                        </div>
                        <form method="POST" action="{{ url('/setting/updateUserPassword/'.$user->id)}}">
                        @csrf
                            <div class="modal-body">
                                <div class="mb-3">
                                  <div class="position-relative">
                                    <input type="password" class="form-control pe-5" name="oldPass" required="required" placeholder="Old Password" />
                                    <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-secondary px-3 py-0 border-0 password-toggle-btn" tabindex="-1" aria-label="Show password">
                                      <i class="fa fa-eye"></i>
                                    </button>
                                  </div>
                                </div>
                                <div class="mb-3">
                                  <div class="position-relative">
                                    <input type="password" class="form-control pe-5" name="newPass" required="required" placeholder="New Password" />
                                    <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-secondary px-3 py-0 border-0 password-toggle-btn" tabindex="-1" aria-label="Show password">
                                      <i class="fa fa-eye"></i>
                                    </button>
                                  </div>
                                </div>
                                <div class="mb-3">
                                  <div class="position-relative">
                                    <input type="password" class="form-control pe-5" name="newPassConfirm" required="required" placeholder="Confirm Password" />
                                    <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-secondary px-3 py-0 border-0 password-toggle-btn" tabindex="-1" aria-label="Show password">
                                      <i class="fa fa-eye"></i>
                                    </button>
                                  </div>
                                </div>
                            </div>
                           
                            <div class="modal-footer">
                              <button type="submit" class="btn btn-primary" onclick="return validatePasswordForm(this)">Change Password</button>
                            </div>
                        </form>
                      </div>
                    </div>
                  </div>
                  
  <div class="modal fade" id="myEditLogin{{$user->id}}" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Login 2FA</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>  
            </div>

            <form method="POST" action="{{ url('/setting/updateLoginSecurity/'.$user->id) }}">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="google2fa_enable" value="1"
                                   {{ $user->loginSecurity && $user->loginSecurity->google2fa_enable ? 'checked' : '' }}>
                            Enable Google 2FA
                        </label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>



    <div class="modal fade" id="myRoleModal{{ $user->id }}" role="dialog">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h4 class="modal-title">Change Role</h4>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form method="POST"
                                                        action="{{ url('/setting/role/' . $user->id) }}">
                                                        @csrf
                                                        <div class="modal-body">
                                                            <select name="user_role" class="form-select" required>
        <option value="">-- Select Role --</option>
        @foreach(($roles ?? []) as $roleName)
            <option value="{{ $roleName }}" @selected($user->role == $roleName)>{{ $roleName }}</option>
        @endforeach
    </select>
                                                        </div>

                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-primary"
                                                                onclick="Validate()">Change Role</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>


                  <div class="modal fade" id="myEditModalUser{{$user->id}}" role="dialog">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h4 class="modal-title">Change Password</h4>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>  
                            </div>
                            <form method="POST" action="{{ url('/setting/updateUserPasswordAdmin/'.$user->id) }}">
                                @csrf
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <div class="position-relative">
                                            <input type="password" class="form-control pe-5" name="newPass" required="required" placeholder="New Password" />
                                            <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-secondary px-3 py-0 border-0 password-toggle-btn" tabindex="-1" aria-label="Show password">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="position-relative">
                                            <input type="password" class="form-control pe-5" name="newPassConfirm" required="required" placeholder="Confirm Password" />
                                            <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-secondary px-3 py-0 border-0 password-toggle-btn" tabindex="-1" aria-label="Show password">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary" onclick="return validatePasswordForm(this)">Change Password</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                

                </td>
              </tr>
              @endforeach @endif
            </tbody>
          </table>
          
          
        </div>
      </div>
    </div>
    
@endsection

@section('footer')


<!-- Scripts Starts For Buyer Delete/View -->
<script type="text/javascript">
  
  $(function () {
    $('[data-bs-toggle="tooltip"]').tooltip();

    $(document).on('click', '.password-toggle-btn', function () {
      var $btn = $(this);
      var $input = $btn.siblings('input');
      var $icon = $btn.find('i');
      if ($input.attr('type') === 'password') {
        $input.attr('type', 'text');
        $icon.addClass('text-primary');
      } else {
        $input.attr('type', 'password');
        $icon.removeClass('text-primary');
      }
    });
  });

  function validatePasswordForm(btn) {
    var $form = $(btn).closest('form');
    var password = $form.find('input[name="newPass"]').val();
    var confirmPassword = $form.find('input[name="newPassConfirm"]').val();
    if (password != confirmPassword) {
      alert("Confirm Passwords do not match.");
      return false;
    }
    return true;
  }

  function user(id){
    location.href = "{{ url('/setting/delete') }}" + '/' +id;
  } 
     
</script>
<!-- Scripts End -->
@endsection
