@extends('adminlte::page')

@section('title', 'Sửa thông tin khách hàng - ' . $customer->name)

@section('content_header')
    <h1>Sửa thông tin khách hàng: {{ $customer->name }}</h1>
@stop

@section('content')
<div class="container-fluid">
    @include('partials._alerts')

    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Thông tin chi tiết khách hàng</h3>
        </div>
        <form action="{{ route('customers.update', $customer) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Tên khách hàng <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $customer->name) }}" required>
                            @error('name')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="phone">Số điện thoại <span class="text-danger">*</span></label>
                            <input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $customer->primary_phone) }}" required>
                            @error('phone')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $customer->email) }}">
                            @error('email')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="date_of_birth">Ngày sinh</label>
                            <input type="date" name="date_of_birth" id="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror" value="{{ old('date_of_birth', $customer->date_of_birth ? $customer->date_of_birth->format('Y-m-d') : '') }}">
                            @error('date_of_birth')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="gender">Giới tính</label>
                            <select name="gender" id="gender" class="form-control @error('gender') is-invalid @enderror">
                                <option value="">-- Chọn giới tính --</option>
                                <option value="male" {{ old('gender', $customer->gender) == 'male' ? 'selected' : '' }}>Nam</option>
                                <option value="female" {{ old('gender', $customer->gender) == 'female' ? 'selected' : '' }}>Nữ</option>
                                <option value="other" {{ old('gender', $customer->gender) == 'other' ? 'selected' : '' }}>Khác</option>
                            </select>
                            @error('gender')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="fb_id">Facebook ID</label>
                            <input type="text" name="fb_id" id="fb_id" class="form-control @error('fb_id') is-invalid @enderror" value="{{ old('fb_id', $customer->fb_id) }}">
                            @error('fb_id')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                </div>

                <hr>
                <h5>Địa chỉ</h5>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="province">Tỉnh/Thành phố</label>
                            <select name="province" id="province" class="form-control select2 @error('province') is-invalid @enderror">
                                <option value="">-- Chọn Tỉnh/Thành phố --</option>
                            </select>
                            @error('province')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="district">Quận/Huyện</label>
                            <select name="district" id="district" class="form-control select2 @error('district') is-invalid @enderror">
                                <option value="">-- Chọn Quận/Huyện --</option>
                            </select>
                            @error('district')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="ward">Phường/Xã</label>
                            <select name="ward" id="ward" class="form-control select2 @error('ward') is-invalid @enderror">
                                <option value="">-- Chọn Phường/Xã --</option>
                            </select>
                            @error('ward')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="street_address">Địa chỉ cụ thể (Số nhà, tên đường)</label>
                    <input type="text" name="street_address" id="street_address" class="form-control @error('street_address') is-invalid @enderror" value="{{ old('street_address', $customer->street_address) }}" placeholder="Ví dụ: 123 Nguyễn Văn Cừ">
                    @error('street_address')
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="full_address">Địa chỉ đầy đủ</label>
                    <textarea name="full_address" id="full_address" class="form-control @error('full_address') is-invalid @enderror" rows="2" readonly>{{ old('full_address', $customer->full_address) }}</textarea>
                    @error('full_address')
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>

                <hr>
                <div class="form-group">
                    <label for="notes">Ghi chú</label>
                    <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes', $customer->notes) }}</textarea>
                    @error('notes')
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="tags">Tags</label>
                    <select name="tags[]" id="tags" class="form-control select2 @error('tags') is-invalid @enderror" multiple>
                        <option value="VIP" {{ in_array('VIP', old('tags', $customer->tags ?? [])) ? 'selected' : '' }}>VIP</option>
                        <option value="Khách quen" {{ in_array('Khách quen', old('tags', $customer->tags ?? [])) ? 'selected' : '' }}>Khách quen</option>
                        <option value="Khách mới" {{ in_array('Khách mới', old('tags', $customer->tags ?? [])) ? 'selected' : '' }}>Khách mới</option>
                    </select>
                    @error('tags')
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Cập nhật thông tin</button>
                <a href="{{ route('customers.show', $customer) }}" class="btn btn-secondary">Hủy</a>
            </div>
        </form>
    </div>
</div>

@push('js')
<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2();

    // Load provinces
    $.get('/api/geo/provinces', function(response) {
        const provinces = response.data;
        let options = '<option value="">-- Chọn Tỉnh/Thành phố --</option>';
        provinces.forEach(function(province) {
            options += `<option value="${province.id}" ${province.id == '{{ old('province', $customer->province) }}' ? 'selected' : ''}>${province.name}</option>`;
        });
        $('#province').html(options);

        // If province is selected, load districts
        if ($('#province').val()) {
            loadDistricts($('#province').val());
        }
    });

    // Province change event
    $('#province').on('change', function() {
        const provinceId = $(this).val();
        if (provinceId) {
            loadDistricts(provinceId);
        } else {
            $('#district').html('<option value="">-- Chọn Quận/Huyện --</option>');
            $('#ward').html('<option value="">-- Chọn Phường/Xã --</option>');
            updateFullAddress();
        }
    });

    // District change event
    $('#district').on('change', function() {
        const districtId = $(this).val();
        if (districtId) {
            loadWards(districtId);
        } else {
            $('#ward').html('<option value="">-- Chọn Phường/Xã --</option>');
            updateFullAddress();
        }
    });

    // Ward and street_address change events
    $('#ward, #street_address').on('change', function() {
        updateFullAddress();
    });

    function loadDistricts(provinceId) {
        $.get(`/api/geo/districts?province_id=${provinceId}`, function(response) {
            const districts = response.data;
            let options = '<option value="">-- Chọn Quận/Huyện --</option>';
            districts.forEach(function(district) {
                options += `<option value="${district.id}" ${district.id == '{{ old('district', $customer->district) }}' ? 'selected' : ''}>${district.name}</option>`;
            });
            $('#district').html(options);

            // If district is selected, load wards
            if ($('#district').val()) {
                loadWards($('#district').val());
            }
            updateFullAddress();
        });
    }

    function loadWards(districtId) {
        $.get(`/api/geo/wards?district_id=${districtId}`, function(response) {
            const wards = response.data;
            let options = '<option value="">-- Chọn Phường/Xã --</option>';
            wards.forEach(function(ward) {
                options += `<option value="${ward.id}" ${ward.id == '{{ old('ward', $customer->ward) }}' ? 'selected' : ''}>${ward.name}</option>`;
            });
            $('#ward').html(options);
            updateFullAddress();
        });
    }

    function updateFullAddress() {
        const street = $('#street_address').val();
        const ward = $('#ward option:selected').text();
        const district = $('#district option:selected').text();
        const province = $('#province option:selected').text();

        let parts = [street, ward, district, province].filter(part =>
            part && !['-- Chọn Phường/Xã --', '-- Chọn Quận/Huyện --', '-- Chọn Tỉnh/Thành phố --'].includes(part)
        );

        $('#full_address').val(parts.join(', '));
    }
});
</script>
@endpush

@stop
