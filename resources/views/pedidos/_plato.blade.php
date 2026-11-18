<div class="card mb-3 plato-card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h3 class="h6 fw-semibold m-0">Plato <span class="numero-plato"></span></h3>
            <button type="button" class="btn btn-sm btn-outline-danger btn-quitar-plato">Quitar</button>
        </div>
        <div class="row g-2">
            <div class="col-md-6">
                <label class="form-label small">Principio</label>
                <select name="platos[{{ $i }}][principio]" class="form-select">
                    <option value="">— Selecciona —</option>
                    @foreach ($principios as $insumo)
                        <option value="{{ $insumo->id_insumo }}" {{ old("platos.$i.principio") == $insumo->id_insumo ? 'selected' : '' }}>
                            {{ $insumo->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label small">Proteína</label>
                <select name="platos[{{ $i }}][proteina]" class="form-select">
                    <option value="">— Selecciona —</option>
                    @foreach ($proteinas as $insumo)
                        <option value="{{ $insumo->id_insumo }}" {{ old("platos.$i.proteina") == $insumo->id_insumo ? 'selected' : '' }}>
                            {{ $insumo->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
            <!-- Acompañamientos: desplegable donde se pueden marcar varios o ninguno -->
            <div class="col-md-6">
                <label class="form-label small">Acompañamientos <span class="text-muted">(opcional)</span></label>
                <div class="dropdown">
                    <button type="button" class="form-select text-start text-truncate"
                            data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                        <span class="resumen-acomp text-muted">Sin acompañamientos</span>
                    </button>
                    <div class="dropdown-menu w-100 p-1 shadow-sm">
                        @forelse ($acompanamientos as $insumo)
                            <label class="dropdown-item rounded d-flex align-items-center gap-2" for="p{{ $i }}a{{ $insumo->id_insumo }}">
                                <input class="form-check-input m-0" type="checkbox" id="p{{ $i }}a{{ $insumo->id_insumo }}"
                                    name="platos[{{ $i }}][acompanamientos][]" value="{{ $insumo->id_insumo }}"
                                    data-nombre="{{ $insumo->nombre }}"
                                    {{ in_array($insumo->id_insumo, old("platos.$i.acompanamientos", [])) ? 'checked' : '' }}>
                                {{ $insumo->nombre }}
                            </label>
                        @empty
                            <span class="dropdown-item-text small text-muted">No hay acompañamientos disponibles hoy.</span>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Adicionales: desplegable con contador y precio de cada uno -->
            @if ($adicionales->count() > 0)
                <div class="col-md-6">
                    <label class="form-label small">Adicionales <span class="text-muted">(opcional)</span></label>
                    <div class="dropdown">
                        <button type="button" class="form-select text-start text-truncate"
                                data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                            <span class="resumen-adic text-muted">Sin adicionales</span>
                        </button>
                        <div class="dropdown-menu w-100 p-1 shadow-sm">
                            @foreach ($adicionales as $adicional)
                                <div class="d-flex justify-content-between align-items-center gap-2 px-3 py-1">
                                    <span class="small lh-sm">
                                        {{ $adicional->nombre }}
                                        <span class="d-block text-muted">${{ number_format($adicional->precio, 0, ',', '.') }}</span>
                                    </span>
                                    <div class="input-group input-group-sm contador flex-nowrap" style="width: 104px">
                                        <button type="button" class="btn btn-outline-secondary btn-menos">−</button>
                                        <input type="text" name="platos[{{ $i }}][adicionales][{{ $adicional->id_plato }}]" readonly
                                            data-precio="{{ $adicional->precio }}" data-nombre="{{ $adicional->nombre }}"
                                            value="{{ old("platos.$i.adicionales.$adicional->id_plato", 0) }}"
                                            class="form-control text-center bg-white px-0">
                                        <button type="button" class="btn btn-outline-secondary btn-mas">+</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
            <div class="col-12">
                <input type="text" name="platos[{{ $i }}][observaciones]" maxlength="255"
                    value="{{ old("platos.$i.observaciones") }}" class="form-control form-control-sm"
                    placeholder="Observaciones para cocina (ej. sin cebolla)">
            </div>
        </div>
    </div>
</div>