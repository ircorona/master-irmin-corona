import Image from 'next/image';

// overrideSrc (Next 14.2.0+): srcset optimizado, src con la URL que tú elijas.
// Componente de servidor: no hace falta 'use client' porque no se pasa ninguna función.
export default function OverrideSrc() {
  return (
    <main>
      <h1>overrideSrc</h1>
      <Image
        src="/img/gato-naranja.jpg"
        overrideSrc="/img/gato-naranja.jpg"
        alt="Gato naranja"
        width={800}
        height={600}
      />
    </main>
  );
}
