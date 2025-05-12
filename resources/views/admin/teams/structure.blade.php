@extends('adminlte::page')

@section('title', 'Sơ đồ Team')

@section('content_header')
    <h1>Sơ đồ cấu trúc Team</h1>
@stop

@section('content')
<div class="card">
    <div class="card-body">
        <h2 class="text-xl font-semibold mb-3">Cấu trúc Teams & Roles</h2>
        <div class="team-tree">
            <ul>
                {{-- Node Gốc: Super Admin(s) --}}
                @if($superAdmins->isNotEmpty())
                    @foreach ($superAdmins as $superAdmin)
                        <li> {{-- Chỉ một <li> cấp cao nhất cho Super Admin --}}
                            <span><i class="fas fa-crown text-warning"></i> {{ $superAdmin->name }} (Super Admin)</span>
                            {{-- Tất cả các node khác nằm trong <ul> lồng bên dưới --}}
                            <ul>
                                {{-- Node Cấp 2: Standard Admins --}}
                                @if($admins->isNotEmpty())
                                    @foreach ($admins as $admin)
                                        <li>
                                            <span><i class="fas fa-user-shield text-danger"></i> {{ $admin->name }} (Admin)</span>
                                            {{-- Giả định Admin không quản lý Leader trực tiếp trong cây này --}}
                                        </li>
                                    @endforeach
                                @endif

                                {{-- Node Cấp 2: Leaders và Staff theo team --}}
                                @if($leaders->isNotEmpty())
                                    @foreach ($leaders as $leader)
                                        <li>
                                            <span><i class="fas fa-user-tie text-primary"></i> {{ $leader->name }} (Leader - Team {{ $leader->manages_team_id }})</span>
                                            @if ($staffByTeam->has($leader->manages_team_id))
                                                <ul>
                                                    @foreach ($staffByTeam->get($leader->manages_team_id) as $staff)
                                                        <li>
                                                            <span><i class="fas fa-user text-success"></i> {{ $staff->name }} (Staff)</span>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <ul><li><span>(Chưa có staff)</span></li></ul>
                                            @endif
                                        </li>
                                    @endforeach
                                {{-- Chỉ hiển thị nếu không có admin nào --}}
                                @elseif($admins->isEmpty())
                                     <li><span>Chưa có Leader nào.</span></li>
                                @endif

                                {{-- Node Cấp 2: Staff chưa gán team --}}
                                @if($unassignedStaff->isNotEmpty())
                                    <li>
                                        <span><i class="fas fa-question-circle text-secondary"></i> Staff chưa gán Team</span>
                                        <ul>
                                             @foreach ($unassignedStaff as $staff)
                                                <li>
                                                    <span><i class="fas fa-user text-secondary"></i> {{ $staff->name }} (Staff)</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </li>
                                @endif

                                {{-- Trường hợp không có node con nào --}}
                                 @if($admins->isEmpty() && $leaders->isEmpty() && $unassignedStaff->isEmpty())
                                     <li><span>Không có cấu trúc cấp dưới để hiển thị.</span></li>
                                 @endif
                            </ul> {{-- Kết thúc <ul> lồng bên dưới Super Admin --}}
                        </li> {{-- Kết thúc <li> của Super Admin --}}
                        {{-- Chỉ hiển thị Super Admin đầu tiên làm gốc nếu có nhiều --}}
                        @break
                    @endforeach
                @else
                    <li><span>Chưa có Super Admin nào để làm gốc cây.</span></li>
                @endif
            </ul>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
    /* Simple CSS Tree */
    .team-tree ul {
        padding-top: 20px; position: relative;
        transition: all 0.5s;
        -webkit-transition: all 0.5s;
        -moz-transition: all 0.5s;
    }

    .team-tree li {
        float: left; text-align: center;
        list-style-type: none;
        position: relative;
        padding: 20px 5px 0 5px;
        transition: all 0.5s;
        -webkit-transition: all 0.5s;
        -moz-transition: all 0.5s;
    }

    /* Sử dụng pseudo elements để vẽ đường nối */
    .team-tree li::before, .team-tree li::after{
        content: '';
        position: absolute; top: 0; right: 50%;
        border-top: 1px solid #ccc;
        width: 50%; height: 20px;
    }
    .team-tree li::after{
        right: auto; left: 50%;
        border-left: 1px solid #ccc;
    }

    /* Xóa đường nối cho node không có cha mẹ hoặc anh em */
    .team-tree li:only-child::after, .team-tree li:only-child::before {
        display: none;
    }
    /* Xóa đường ngang bên phải của node con đầu tiên */
    /* Xóa đường ngang bên trái của node con cuối cùng */
    .team-tree li:first-child::before, .team-tree li:last-child::after{
        border: 0 none;
    }
    /* Thêm đường dọc cho node con cuối cùng */
    .team-tree li:last-child::before{
        border-right: 1px solid #ccc;
        border-radius: 0 5px 0 0;
        -webkit-border-radius: 0 5px 0 0;
        -moz-border-radius: 0 5px 0 0;
    }
    .team-tree li:first-child::after{
        border-radius: 5px 0 0 0;
        -webkit-border-radius: 5px 0 0 0;
        -moz-border-radius: 5px 0 0 0;
    }

    /* Đường nối thẳng xuống từ cha mẹ đến node con */
    .team-tree ul ul::before{
        content: '';
        position: absolute; top: 0; left: 50%;
        border-left: 1px solid #ccc;
        width: 0; height: 20px;
    }

    /* Định dạng cho nội dung node (span) */
    .team-tree li span {
        border: 1px solid #ccc;
        padding: 5px 10px;
        display: inline-block;
        border-radius: 5px;
        background-color: #fff;
        min-width: 150px; /* Đảm bảo độ rộng tối thiểu */
        transition: all 0.5s;
        -webkit-transition: all 0.5s;
        -moz-transition: all 0.5s;
    }
    .team-tree li span:hover, .team-tree li span:hover+ul li span {
        background: #e8f4ff;
        color: #000;
        border: 1px solid #94a0b4;
    }
    .team-tree li span:hover+ul li::after,
    .team-tree li span:hover+ul li::before,
    .team-tree li span:hover+ul::before,
    .team-tree li span:hover+ul ul::before{
        border-color: #94a0b4;
    }

</style>
@stop
