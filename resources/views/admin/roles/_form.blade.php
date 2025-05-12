@csrf
<div class="form-group">
    <label for="name">Role Name</label>
    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
           value="{{ old('name', isset($role) ? $role->name : '') }}" required>
    @error('name')
        <span class="invalid-feedback" role="alert">
            <strong>{{ $message }}</strong>
        </span>
    @enderror
</div>

<div class="form-group">
    <label>Permissions</label>
    <div class="row">
        @foreach ($permissions as $id => $name)
            <div class="col-md-3">
                <div class="custom-control custom-checkbox">
                    <input class="custom-control-input" type="checkbox" id="permission_{{ $id }}" name="permissions[]" value="{{ $id }}"
                           {{ (isset($rolePermissions) && in_array($id, $rolePermissions)) ? 'checked' : '' }}>
                    <label for="permission_{{ $id }}" class="custom-control-label">{{ $name }}</label>
                </div>
            </div>
        @endforeach
    </div>
     @error('permissions')
        <span class="text-danger" role="alert">
            <strong>{{ $message }}</strong>
        </span>
    @enderror
</div>

<div class="form-group">
    <button type="submit" class="btn btn-success">{{ isset($role) ? 'Update Role' : 'Create Role' }}</button>
    <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">Cancel</a>
</div>
