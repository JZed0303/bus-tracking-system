<div class="card">
    <div class="card-body">

        <div class="mb-3">
            <label class="form-label">Route Name</label>
            <input type="text" name="name" class="form-control"
                   value="{{ old('name', $route->name ?? '') }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Company</label>
            <select name="company_id" class="form-select">
                <option value="">Shared Route</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}"
                        @selected(old('company_id', $route->company_id ?? '') == $company->id)>
                        {{ $company->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>

    </div>
</div>
