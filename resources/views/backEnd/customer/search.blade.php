@if($customers)
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <tbody>
                @foreach($customers as $value)
                <tr>
                    <td>
                        <a href="{{route('customers.profile',['id'=>$value->id])}}" class="d-flex align-items-center text-decoration-none">
                            <div class="avatar avatar-sm me-2">
                                <span class="avatar-initial bg-label-primary rounded-circle">
                                    <i class="ri ri-user-line"></i>
                                </span>
                            </div>
                            <span class="fw-medium">{{$value->name}}</span>
                        </a>
                    </td>
                    <td class="text-muted text-end">
                        {{$value->phone ?? 'N/A'}}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="text-center py-3 text-muted">
        <i class="ri ri-search-line me-1"></i> No customers found
    </div>
@endif
