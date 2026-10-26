{{-- Las casillas del 104 a escribir en el portal y los totales para comparar. Recibe $guia y $money. --}}
@forelse ($guia['casillas'] as $seccion => $filas)
    <p class="ct-tit" style="margin:14px 0 6px">{{ $seccion }}</p>
    @foreach ($filas as $f)
        @php $valor = $f['conteo'] ? (string) (int) $f['valor'] : number_format($f['valor'], 2, '.', ''); @endphp
        <div class="ct-casilla" x-data="{ copiado: false }">
            <span class="ct-casilla-n">{{ $f['codigo'] }}</span>
            <span class="ct-casilla-t">
                @if ($f['ayuda'])
                    <span class="ct-casilla-ayuda">{{ $f['ayuda'] }}</span>
                    <span class="ct-casilla-oficial">{{ $f['nombre'] }}</span>
                @else
                    {{ $f['nombre'] }}
                @endif
            </span>
            <span class="ct-casilla-v">{{ $f['conteo'] ? $valor : $money($f['valor']) }}</span>
            <button type="button" class="ct-pestana"
                    x-on:click="navigator.clipboard.writeText('{{ $valor }}'); copiado = true; setTimeout(() => copiado = false, 1500)"
                    :aria-label="'Copiar casilla {{ $f['codigo'] }}'">
                <span x-text="copiado ? 'Copiado' : 'Copiar'"></span>
            </button>
        </div>
    @endforeach
@empty
    <p class="ct-fila-s">Mes sin movimiento: se declara en cero, sin escribir ninguna casilla.</p>
@endforelse

<p class="ct-tit" style="margin:18px 0 6px">El portal debe mostrarte</p>
@foreach ($guia['comparar'] as [$nombre, $codigo, $valor])
    <div class="ct-casilla ct-casilla-total">
        <span class="ct-casilla-n">{{ $codigo }}</span>
        <span class="ct-casilla-t">{{ $nombre }}</span>
        <span class="ct-casilla-v">{{ $money($valor) }}</span>
    </div>
@endforeach
