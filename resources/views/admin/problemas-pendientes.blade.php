@extends('layouts.main')

@section('title', 'Problemas pendientes de aprobación')

@section('styles')
<style>
tr:target td { background-color: #fffbeb; outline: 2px solid #ed8936; }
</style>
@endsection

@section('content')
<div style="max-width:1100px; margin:2rem auto; padding:0 1rem;">
    <h1 style="margin-bottom:0.5rem; color:#2d3748;">⏳ Problemas pendientes de aprobación</h1>
    <p style="color:#718096; margin-bottom:1.5rem;">
        {{ $problemas->total() }} problema(s) esperando revisión.
    </p>

    @if($problemas->isEmpty())
        <div style="background:#f7fafc; border:1px solid #e2e8f0; border-radius:8px; padding:3rem; text-align:center; color:#718096;">
            No hay problemas pendientes de aprobación. ✅
        </div>
    @else
        <div style="overflow-x:auto; background:white; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:#f7fafc; border-bottom:2px solid #e2e8f0;">
                        <th style="padding:0.75rem 1rem; text-align:left; color:#4a5568; font-size:0.875rem;">#</th>
                        <th style="padding:0.75rem 1rem; text-align:left; color:#4a5568; font-size:0.875rem;">Enunciado</th>
                        <th style="padding:0.75rem 1rem; text-align:left; color:#4a5568; font-size:0.875rem;">Tags</th>
                        <th style="padding:0.75rem 1rem; text-align:left; color:#4a5568; font-size:0.875rem;">Proponente</th>
                        <th style="padding:0.75rem 1rem; text-align:left; color:#4a5568; font-size:0.875rem;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($problemas as $problema)
                        <tr id="problema-{{ $problema->id }}" style="border-bottom:1px solid #e2e8f0;">
                            <td style="padding:0.75rem 1rem; color:#718096; font-size:0.875rem; white-space:nowrap;">
                                <a href="{{ route('problemas.show', $problema->id) }}" style="color:#4299e1; text-decoration:none; font-weight:600;">
                                    #{{ $problema->id }}
                                </a>
                            </td>
                            <td style="padding:0.75rem 1rem; max-width:360px;">
                                <div style="font-size:0.9rem; color:#2d3748; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="{{ $problema->problem_tex }}">
                                    {{ Str::limit(strip_tags($problema->problem_tex), 120, '…') }}
                                </div>
                            </td>
                            <td style="padding:0.75rem 1rem;">
                                <div style="display:flex; flex-wrap:wrap; gap:0.25rem;">
                                    @foreach($problema->tags as $tag)
                                        <span style="background:#e9d8fd; color:#553c9a; padding:0.15rem 0.5rem; border-radius:10px; font-size:0.75rem;">{{ $tag->tag }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td style="padding:0.75rem 1rem; font-size:0.875rem; color:#4a5568; white-space:nowrap;">
                                {{ $problema->proponent->name ?? '—' }}
                                <div style="color:#a0aec0; font-size:0.75rem;">{{ $problema->proponent->email ?? '' }}</div>
                            </td>
                            <td style="padding:0.75rem 1rem; white-space:nowrap;">
                                <form action="{{ route('admin.problemas.aprobar', $problema->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button type="submit"
                                            onclick="return confirm('¿Aprobar el problema #{{ $problema->id }}?')"
                                            style="background:#48bb78; color:white; border:none; padding:0.4rem 0.9rem; border-radius:4px; cursor:pointer; font-size:0.875rem;">
                                        ✅ Aprobar
                                    </button>
                                </form>
                                <a href="{{ route('problemas.edit', $problema->id) }}"
                                   style="margin-left:0.5rem; background:#4299e1; color:white; padding:0.4rem 0.9rem; border-radius:4px; font-size:0.875rem; text-decoration:none;">
                                    ✏️ Editar
                                </a>
                                <form action="{{ route('problemas.destroy', $problema->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            onclick="return confirm('¿Eliminar el problema #{{ $problema->id }}? Esta acción no se puede deshacer.')"
                                            style="margin-left:0.5rem; background:#fc8181; color:white; border:none; padding:0.4rem 0.9rem; border-radius:4px; cursor:pointer; font-size:0.875rem;">
                                        🗑️ Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top:1.5rem; display:flex; justify-content:center;">
            {{ $problemas->links() }}
        </div>
    @endif

    <div style="margin-top:1.5rem;">
        <a href="{{ route('admin.users.index') }}" style="color:#718096; text-decoration:none;">← Volver a usuarios</a>
    </div>
</div>
@endsection
