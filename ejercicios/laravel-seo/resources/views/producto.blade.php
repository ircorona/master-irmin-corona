@extends('layout')

@push('head')
    {{-- JSON-LD de la página, empujado al <head> desde la vista --}}
    <script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'Product', 'name' => 'Zapatillas de trail']) !!}</script>
@endpush

@section('contenido')
    <h1>Zapatillas de trail</h1>
    <p>Suela de 5 mm, 280 g.</p>
    <a href="{{ route('producto') }}">Enlace con route()</a>
@endsection
