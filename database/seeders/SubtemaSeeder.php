<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tema;
use App\Models\Subtema;

class SubtemaSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'Geometría' => [
                'Ángulos',
                'Congruencias',
                'Construcciones con regla y compás',
                'Triángulos',
                'Cuadriláteros',
                'Circunferencias',
                'Vectores',
                'Geometría analítica',
                'Geometría combinatoria',
                'Transformaciones del plano',
                'Lugares geométricos',
                'Áreas',
                'Geometrías no euclidianas',
            ],
            'Aritmética' => [
                'Sistemas de numeración y codificaciones',
                'Introducción axiomática de los números',
                'Series',
                'Divisibilidad',
            ],
            'Teoría de números' => [
                'Aritmética modular',
                'Teorema de Fermat-Euler',
                'Teorema chino del resto',
                'Ecuaciones diofánticas',
                'Fracciones continuas',
                'Racionalidad e irracionalidad',
                'Algoritmo de Euclides',
            ],
            'Combinatoria' => [
                'Regla del producto',
                'Números combinatorios',
                'Particiones',
                'Funciones generatrices',
                'Diagramas de Young',
            ],
            'Grafos' => [
                'Propiedades de los grafos',
                'Árboles',
                'Fórmula de Euler',
                'Grafos eulerianos y hamiltonianos',
                'Coloración de grafos',
                'Ramsey, Turán y cliques',
                'Teorema de Hall y matching',
                'Espectro y clustering',
                'Grafos dirigidos',
                'Algoritmos de grafos',
            ],
            'Álgebra' => [
                'Ecuaciones',
                'Desigualdades',
                'Polinomios',
                'Matrices y transformaciones afines',
                'Estructuras algebraicas',
                'Complejos',
            ],
            'Discreta' => [
                'Conjuntos y funciones',
                'Lógica',
                'Algoritmos y autómatas',
                'Análisis de juegos',
                'Teoría de juegos',
            ],
            'Cálculo' => [
                'Derivadas',
                'Análisis de funciones',
                'Teoremas del análisis y contraejemplos',
                'Optimización',
                'Series de Taylor',
                'Integrales',
                'Volúmenes',
            ],
            'Probabilidad' => [
                'Combinaciones de sucesos',
                'Probabilidad condicional',
                'Probabilidad geométrica',
                'Esperanza/valor esperado',
                'Distribuciones',
                'Paradojas de probabilidad',
            ],
            'Métodos' => [
                'Coloración',
                'Palomar',
                'Paridad',
                'Simetría',
                'Principio del extremo',
                'Inducción',
                'Descenso infinito',
                'Invariantes y semiinvariantes',
                'Reductio ad absurdum',
                'Estimaciones',
            ],
            'Miscelánea' => [],
        ];

        foreach ($data as $temaNombre => $subtemas) {
            $tema = Tema::where('tema', $temaNombre)->first();

            if (!$tema) {
                $this->command->warn("Tema no encontrado: {$temaNombre}");
                continue;
            }

            foreach ($subtemas as $subtema) {
                Subtema::firstOrCreate([
                    'nombre' => $subtema,
                    'tema_id' => $tema->id,
                ]);
            }
        }

        $this->command->info('Subtemas creados correctamente.');
    }
}
