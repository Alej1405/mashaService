<?php

namespace Database\Seeders;

use App\Models\AccountPlan;
use Illuminate\Database\Seeder;

class AccountPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            // NIVEL 1 - BASE
            ['code' => '1', 'name' => 'ACTIVO', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => null, 'level' => 1, 'accepts_movements' => false, 'modulo' => 'base'],
            ['code' => '2', 'name' => 'PASIVO', 'type' => 'pasivo', 'nature' => 'acreedora', 'parent_code' => null, 'level' => 1, 'accepts_movements' => false, 'modulo' => 'base'],
            ['code' => '3', 'name' => 'PATRIMONIO NETO', 'type' => 'patrimonio', 'nature' => 'acreedora', 'parent_code' => null, 'level' => 1, 'accepts_movements' => false, 'modulo' => 'base'],
            ['code' => '4', 'name' => 'INGRESOS', 'type' => 'ingreso', 'nature' => 'acreedora', 'parent_code' => null, 'level' => 1, 'accepts_movements' => false, 'modulo' => 'base'],
            ['code' => '5', 'name' => 'COSTOS', 'type' => 'costo', 'nature' => 'deudora', 'parent_code' => null, 'level' => 1, 'accepts_movements' => false, 'modulo' => 'base'],
            ['code' => '6', 'name' => 'GASTOS', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => null, 'level' => 1, 'accepts_movements' => false, 'modulo' => 'base'],

            // ACTIVO CORRIENTE - BASE
            ['code' => '1.1', 'name' => 'Activo Corriente', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1', 'level' => 2, 'accepts_movements' => false, 'modulo' => 'base'],
            ['code' => '1.1.01', 'name' => 'Efectivo y equivalentes del efectivo', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.1', 'level' => 3, 'accepts_movements' => false, 'modulo' => 'base'],
            ['code' => '1.1.01.01', 'name' => 'Caja general', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.1.01', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '1.1.01.02', 'name' => 'Caja chica', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.1.01', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '1.1.01.03', 'name' => 'Bancos cuenta corriente', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.1.01', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '1.1.01.04', 'name' => 'Bancos cuenta de ahorros', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.1.01', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '1.1.02', 'name' => 'Activos financieros', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.1', 'level' => 3, 'accepts_movements' => false, 'modulo' => 'base'],
            ['code' => '1.1.02.01', 'name' => 'Cuentas por cobrar clientes', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.1.02', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '1.1.02.02', 'name' => 'Documentos por cobrar', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.1.02', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '1.1.02.03', 'name' => '(-) Provisión cuentas incobrables', 'type' => 'activo', 'nature' => 'acreedora', 'parent_code' => '1.1.02', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '1.1.04', 'name' => 'Servicios y otros pagos anticipados', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.1', 'level' => 3, 'accepts_movements' => false, 'modulo' => 'base'],
            ['code' => '1.1.04.01', 'name' => 'Seguros pagados por anticipado', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.1.04', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '1.1.04.02', 'name' => 'Arriendos pagados por anticipado', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.1.04', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '1.1.04.03', 'name' => 'Anticipos a proveedores', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.1.04', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '1.1.05', 'name' => 'Activos por impuestos corrientes', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.1', 'level' => 3, 'accepts_movements' => false, 'modulo' => 'base'],
            ['code' => '1.1.05.01', 'name' => 'Crédito tributario IVA', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.1.05', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '1.1.05.02', 'name' => 'Crédito tributario impuesto a la renta', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.1.05', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '1.1.05.03', 'name' => 'Anticipo impuesto a la renta', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.1.05', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],

            // ACTIVO NO CORRIENTE - BASE
            ['code' => '1.2', 'name' => 'Activo No Corriente', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1', 'level' => 2, 'accepts_movements' => false, 'modulo' => 'base'],
            ['code' => '1.2.01', 'name' => 'Propiedades, planta y equipo', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.2', 'level' => 3, 'accepts_movements' => false, 'modulo' => 'base'],
            ['code' => '1.2.01.01', 'name' => 'Terrenos', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.2.01', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '1.2.01.02', 'name' => 'Edificios y construcciones', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.2.01', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '1.2.01.03', 'name' => 'Muebles y enseres', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.2.01', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '1.2.01.04', 'name' => 'Maquinaria y equipo', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.2.01', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '1.2.01.05', 'name' => 'Equipo de computación', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.2.01', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '1.2.01.06', 'name' => 'Vehículos', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.2.01', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '1.2.01.07', 'name' => '(-) Depreciación acumulada PPE', 'type' => 'activo', 'nature' => 'acreedora', 'parent_code' => '1.2.01', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '1.2.04', 'name' => 'Activo intangible', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.2', 'level' => 3, 'accepts_movements' => false, 'modulo' => 'base'],
            ['code' => '1.2.04.01', 'name' => 'Marcas y patentes', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.2.04', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '1.2.04.02', 'name' => 'Programas de computación', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.2.04', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '1.2.04.03', 'name' => '(-) Amortización acumulada intangibles', 'type' => 'activo', 'nature' => 'acreedora', 'parent_code' => '1.2.04', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],

            // PASIVO - BASE
            ['code' => '2.1', 'name' => 'Pasivo Corriente', 'type' => 'pasivo', 'nature' => 'acreedora', 'parent_code' => '2', 'level' => 2, 'accepts_movements' => false, 'modulo' => 'base'],
            ['code' => '2.1.01', 'name' => 'Cuentas y documentos por pagar', 'type' => 'pasivo', 'nature' => 'acreedora', 'parent_code' => '2.1', 'level' => 3, 'accepts_movements' => false, 'modulo' => 'base'],
            ['code' => '2.1.01.01', 'name' => 'Proveedores locales por pagar', 'type' => 'pasivo', 'nature' => 'acreedora', 'parent_code' => '2.1.01', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '2.1.01.02', 'name' => 'Documentos por pagar proveedores', 'type' => 'pasivo', 'nature' => 'acreedora', 'parent_code' => '2.1.01', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '2.1.02', 'name' => 'Obligaciones con instituciones financieras CP', 'type' => 'pasivo', 'nature' => 'acreedora', 'parent_code' => '2.1', 'level' => 3, 'accepts_movements' => false, 'modulo' => 'base'],
            ['code' => '2.1.02.01', 'name' => 'Préstamos bancarios CP', 'type' => 'pasivo', 'nature' => 'acreedora', 'parent_code' => '2.1.02', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '2.1.02.02', 'name' => 'Sobregiros bancarios', 'type' => 'pasivo', 'nature' => 'acreedora', 'parent_code' => '2.1.02', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '2.1.03', 'name' => 'Provisiones corrientes', 'type' => 'pasivo', 'nature' => 'acreedora', 'parent_code' => '2.1', 'level' => 3, 'accepts_movements' => false, 'modulo' => 'base'],
            ['code' => '2.1.03.01', 'name' => 'Provisión jubilación patronal', 'type' => 'pasivo', 'nature' => 'acreedora', 'parent_code' => '2.1.03', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '2.1.03.02', 'name' => 'Provisión desahucio', 'type' => 'pasivo', 'nature' => 'acreedora', 'parent_code' => '2.1.03', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '2.1.04', 'name' => 'Otras obligaciones corrientes', 'type' => 'pasivo', 'nature' => 'acreedora', 'parent_code' => '2.1', 'level' => 3, 'accepts_movements' => false, 'modulo' => 'base'],
            ['code' => '2.1.04.01', 'name' => 'IVA en ventas por pagar', 'type' => 'pasivo', 'nature' => 'acreedora', 'parent_code' => '2.1.04', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '2.1.04.02', 'name' => 'Retenciones IVA por pagar', 'type' => 'pasivo', 'nature' => 'acreedora', 'parent_code' => '2.1.04', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '2.1.04.03', 'name' => 'Retenciones fuente por pagar', 'type' => 'pasivo', 'nature' => 'acreedora', 'parent_code' => '2.1.04', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '2.1.04.04', 'name' => 'IESS por pagar aporte patronal', 'type' => 'pasivo', 'nature' => 'acreedora', 'parent_code' => '2.1.04', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '2.1.04.05', 'name' => 'IESS por pagar aporte personal', 'type' => 'pasivo', 'nature' => 'acreedora', 'parent_code' => '2.1.04', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '2.1.04.06', 'name' => 'Décimo tercer sueldo por pagar', 'type' => 'pasivo', 'nature' => 'acreedora', 'parent_code' => '2.1.04', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '2.1.04.07', 'name' => 'Décimo cuarto sueldo por pagar', 'type' => 'pasivo', 'nature' => 'acreedora', 'parent_code' => '2.1.04', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '2.1.04.08', 'name' => 'Vacaciones por pagar', 'type' => 'pasivo', 'nature' => 'acreedora', 'parent_code' => '2.1.04', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '2.1.04.09', 'name' => 'Utilidades trabajadores por pagar', 'type' => 'pasivo', 'nature' => 'acreedora', 'parent_code' => '2.1.04', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '2.1.04.10', 'name' => 'Impuesto a la renta por pagar', 'type' => 'pasivo', 'nature' => 'acreedora', 'parent_code' => '2.1.04', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '2.1.05', 'name' => 'Anticipos de clientes', 'type' => 'pasivo', 'nature' => 'acreedora', 'parent_code' => '2.1', 'level' => 3, 'accepts_movements' => false, 'modulo' => 'base'],
            ['code' => '2.1.05.01', 'name' => 'Anticipos de clientes corriente', 'type' => 'pasivo', 'nature' => 'acreedora', 'parent_code' => '2.1.05', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '2.2', 'name' => 'Pasivo No Corriente', 'type' => 'pasivo', 'nature' => 'acreedora', 'parent_code' => '2', 'level' => 2, 'accepts_movements' => false, 'modulo' => 'base'],
            ['code' => '2.2.01', 'name' => 'Obligaciones financieras LP', 'type' => 'pasivo', 'nature' => 'acreedora', 'parent_code' => '2.2', 'level' => 3, 'accepts_movements' => false, 'modulo' => 'base'],
            ['code' => '2.2.01.01', 'name' => 'Préstamos bancarios LP', 'type' => 'pasivo', 'nature' => 'acreedora', 'parent_code' => '2.2.01', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '2.2.05', 'name' => 'Provisiones por beneficios a empleados', 'type' => 'pasivo', 'nature' => 'acreedora', 'parent_code' => '2.2', 'level' => 3, 'accepts_movements' => false, 'modulo' => 'base'],
            ['code' => '2.2.05.01', 'name' => 'Provisión jubilación patronal LP', 'type' => 'pasivo', 'nature' => 'acreedora', 'parent_code' => '2.2.05', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '2.2.05.02', 'name' => 'Provisión desahucio LP', 'type' => 'pasivo', 'nature' => 'acreedora', 'parent_code' => '2.2.05', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'base'],

            // PATRIMONIO - BASE
            ['code' => '3.1', 'name' => 'Capital', 'type' => 'patrimonio', 'nature' => 'acreedora', 'parent_code' => '3', 'level' => 2, 'accepts_movements' => false, 'modulo' => 'base'],
            ['code' => '3.1.01', 'name' => 'Capital suscrito y pagado', 'type' => 'patrimonio', 'nature' => 'acreedora', 'parent_code' => '3.1', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '3.1.02', 'name' => '(-) Capital suscrito no pagado', 'type' => 'patrimonio', 'nature' => 'deudora', 'parent_code' => '3.1', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '3.4', 'name' => 'Reservas', 'type' => 'patrimonio', 'nature' => 'acreedora', 'parent_code' => '3', 'level' => 2, 'accepts_movements' => false, 'modulo' => 'base'],
            ['code' => '3.4.01', 'name' => 'Reserva legal', 'type' => 'patrimonio', 'nature' => 'acreedora', 'parent_code' => '3.4', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '3.4.02', 'name' => 'Reserva estatutaria', 'type' => 'patrimonio', 'nature' => 'acreedora', 'parent_code' => '3.4', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '3.4.03', 'name' => 'Reserva facultativa', 'type' => 'patrimonio', 'nature' => 'acreedora', 'parent_code' => '3.4', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '3.6', 'name' => 'Resultados acumulados', 'type' => 'patrimonio', 'nature' => 'acreedora', 'parent_code' => '3', 'level' => 2, 'accepts_movements' => false, 'modulo' => 'base'],
            ['code' => '3.6.01', 'name' => 'Ganancias acumuladas', 'type' => 'patrimonio', 'nature' => 'acreedora', 'parent_code' => '3.6', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '3.6.02', 'name' => '(-) Pérdidas acumuladas', 'type' => 'patrimonio', 'nature' => 'deudora', 'parent_code' => '3.6', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '3.7', 'name' => 'Resultados del ejercicio', 'type' => 'patrimonio', 'nature' => 'acreedora', 'parent_code' => '3', 'level' => 2, 'accepts_movements' => false, 'modulo' => 'base'],
            ['code' => '3.7.01', 'name' => 'Ganancia neta del período', 'type' => 'patrimonio', 'nature' => 'acreedora', 'parent_code' => '3.7', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '3.7.02', 'name' => '(-) Pérdida neta del período', 'type' => 'patrimonio', 'nature' => 'deudora', 'parent_code' => '3.7', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'base'],

            // INGRESOS - BASE
            ['code' => '4.1', 'name' => 'Ingresos de actividades ordinarias', 'type' => 'ingreso', 'nature' => 'acreedora', 'parent_code' => '4', 'level' => 2, 'accepts_movements' => false, 'modulo' => 'base'],
            ['code' => '4.3', 'name' => 'Otros ingresos', 'type' => 'ingreso', 'nature' => 'acreedora', 'parent_code' => '4', 'level' => 2, 'accepts_movements' => false, 'modulo' => 'base'],
            ['code' => '4.3.01', 'name' => 'Intereses financieros ganados', 'type' => 'ingreso', 'nature' => 'acreedora', 'parent_code' => '4.3', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '4.3.02', 'name' => 'Otras rentas', 'type' => 'ingreso', 'nature' => 'acreedora', 'parent_code' => '4.3', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '4.3.03', 'name' => 'Utilidad en venta de activos', 'type' => 'ingreso', 'nature' => 'acreedora', 'parent_code' => '4.3', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'base'],

            // GASTOS - BASE
            ['code' => '6.1', 'name' => 'Gastos de administración y ventas', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6', 'level' => 2, 'accepts_movements' => false, 'modulo' => 'base'],
            ['code' => '6.1.01', 'name' => 'Sueldos y salarios', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '6.1.02', 'name' => 'Beneficios sociales', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '6.1.03', 'name' => 'Aporte patronal IESS', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '6.1.04', 'name' => 'Honorarios profesionales', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '6.1.05', 'name' => 'Arrendamiento operativo', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '6.1.06', 'name' => 'Mantenimiento y reparaciones', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '6.1.07', 'name' => 'Combustibles y lubricantes', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '6.1.08', 'name' => 'Suministros y materiales de oficina', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '6.1.09', 'name' => 'Transporte y movilización', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '6.1.10', 'name' => 'Publicidad y propaganda', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '6.1.11', 'name' => 'Seguros y reaseguros', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '6.1.12', 'name' => 'Depreciación activos fijos', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '6.1.13', 'name' => 'Amortización intangibles', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '6.1.14', 'name' => 'Servicios básicos', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '6.1.15', 'name' => 'Impuestos contribuciones y otros', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '6.1.16', 'name' => 'Gastos de gestión', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '6.1.17', 'name' => 'Gastos de viaje', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '6.1.18', 'name' => 'Comisiones en ventas', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '6.1.19', 'name' => 'Otros gastos administración y ventas', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '6.2', 'name' => 'Gastos financieros', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6', 'level' => 2, 'accepts_movements' => false, 'modulo' => 'base'],
            ['code' => '6.2.01', 'name' => 'Intereses bancarios', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.2', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '6.2.02', 'name' => 'Comisiones bancarias', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.2', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '6.2.03', 'name' => 'Intereses y multas SRI', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.2', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '6.3', 'name' => 'Otros gastos', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6', 'level' => 2, 'accepts_movements' => false, 'modulo' => 'base'],
            ['code' => '6.3.01', 'name' => 'Pérdida en venta de activos', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.3', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '6.3.02', 'name' => 'Gastos no deducibles', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.3', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'base'],
            ['code' => '6.3.03', 'name' => 'Otros gastos', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.3', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'base'],

            // GRUPO PRODUCTOS
            ['code' => '4.1.01', 'name' => 'Ventas de bienes', 'type' => 'ingreso', 'nature' => 'acreedora', 'parent_code' => '4.1', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'productos'],
            ['code' => '4.1.03', 'name' => '(-) Descuentos en ventas', 'type' => 'ingreso', 'nature' => 'deudora', 'parent_code' => '4.1', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'productos'],
            ['code' => '4.1.04', 'name' => '(-) Devoluciones en ventas', 'type' => 'ingreso', 'nature' => 'deudora', 'parent_code' => '4.1', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'productos'],
            ['code' => '1.1.03', 'name' => 'Inventarios', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.1', 'level' => 3, 'accepts_movements' => false, 'modulo' => 'productos'],
            ['code' => '1.1.03.04', 'name' => 'Inventario de productos terminados', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.1.03', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'productos'],
            ['code' => '5.1', 'name' => 'Costo de ventas y producción', 'type' => 'costo', 'nature' => 'deudora', 'parent_code' => '5', 'level' => 2, 'accepts_movements' => false, 'modulo' => 'productos'],
            ['code' => '5.1.04', 'name' => 'Costo de productos terminados vendidos', 'type' => 'costo', 'nature' => 'deudora', 'parent_code' => '5.1', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'productos'],

            // GRUPO SERVICIOS
            ['code' => '4.1.02', 'name' => 'Prestación de servicios', 'type' => 'ingreso', 'nature' => 'acreedora', 'parent_code' => '4.1', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'servicios'],
            ['code' => '6.1.20', 'name' => 'Costo directo de servicios prestados', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'servicios'],
            ['code' => '6.1.21', 'name' => 'Subcontratos de servicios', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'servicios'],

            // GRUPO MANUFACTURA
            ['code' => '1.1.03.01', 'name' => 'Inventario de insumos', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.1.03', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'manufactura'],
            ['code' => '1.1.03.02', 'name' => 'Inventario de materias primas', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.1.03', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'manufactura'],
            ['code' => '1.1.03.03', 'name' => 'Inventario de productos en proceso', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.1.03', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'manufactura'],
            ['code' => '1.1.03.05', 'name' => '(-) Provisión valor neto realizable', 'type' => 'activo', 'nature' => 'acreedora', 'parent_code' => '1.1.03', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'manufactura'],
            ['code' => '5.1.01', 'name' => 'Costo de materias primas utilizadas', 'type' => 'costo', 'nature' => 'deudora', 'parent_code' => '5.1', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'manufactura'],
            ['code' => '5.1.02', 'name' => 'Costo de insumos utilizados', 'type' => 'costo', 'nature' => 'deudora', 'parent_code' => '5.1', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'manufactura'],
            ['code' => '5.1.03', 'name' => 'Costo de mano de obra directa', 'type' => 'costo', 'nature' => 'deudora', 'parent_code' => '5.1', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'manufactura'],
            ['code' => '5.1.05', 'name' => 'Costos indirectos de fabricación', 'type' => 'costo', 'nature' => 'deudora', 'parent_code' => '5.1', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'manufactura'],

            // GRUPO LOGISTICA
            ['code' => '1.1.06', 'name' => 'Otros activos corrientes', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.1', 'level' => 3, 'accepts_movements' => false, 'modulo' => 'logistica'],
            ['code' => '1.1.06.01', 'name' => 'Materiales de empaque y embalaje', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.1.06', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'logistica'],
            ['code' => '1.1.06.02', 'name' => 'Suministros de almacén', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.1.06', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'logistica'],
            ['code' => '1.2.01.08', 'name' => 'Equipos de almacenamiento', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.2.01', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'logistica'],
            ['code' => '1.2.01.09', 'name' => '(-) Depreciación equipos almacenamiento', 'type' => 'activo', 'nature' => 'acreedora', 'parent_code' => '1.2.01', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'logistica'],
            ['code' => '6.1.22', 'name' => 'Gastos de almacenamiento y bodegaje', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'logistica'],
            ['code' => '6.1.23', 'name' => 'Fletes y acarreos locales', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'logistica'],
            ['code' => '6.1.24', 'name' => 'Embalaje y empaque', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'logistica'],
            ['code' => '6.1.25', 'name' => 'Seguros de mercadería en tránsito', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'logistica'],
            ['code' => '6.1.26', 'name' => 'Gastos de distribución', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'logistica'],

            // GRUPO COMERCIO EXTERIOR
            ['code' => '1.1.07', 'name' => 'Importaciones en tránsito', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.1', 'level' => 3, 'accepts_movements' => false, 'modulo' => 'comercio_exterior'],
            ['code' => '1.1.07.01', 'name' => 'Mercadería en tránsito importación', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.1.07', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'comercio_exterior'],
            ['code' => '1.1.07.02', 'name' => 'Anticipos a proveedores exterior', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.1.07', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'comercio_exterior'],
            ['code' => '1.1.05.04', 'name' => 'IVA en importaciones', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.1.05', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'comercio_exterior'],
            ['code' => '2.1.01.03', 'name' => 'Proveedores del exterior por pagar', 'type' => 'pasivo', 'nature' => 'acreedora', 'parent_code' => '2.1.01', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'comercio_exterior'],
            ['code' => '2.1.06', 'name' => 'Otros pasivos corrientes', 'type' => 'pasivo', 'nature' => 'acreedora', 'parent_code' => '2.1', 'level' => 3, 'accepts_movements' => false, 'modulo' => 'comercio_exterior'],
            ['code' => '2.1.06.01', 'name' => 'Derechos arancelarios por pagar', 'type' => 'pasivo', 'nature' => 'acreedora', 'parent_code' => '2.1.06', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'comercio_exterior'],
            ['code' => '2.1.06.02', 'name' => 'FODINFA por pagar', 'type' => 'pasivo', 'nature' => 'acreedora', 'parent_code' => '2.1.06', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'comercio_exterior'],
            ['code' => '2.1.06.03', 'name' => 'Salvaguardias por pagar', 'type' => 'pasivo', 'nature' => 'acreedora', 'parent_code' => '2.1.06', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'comercio_exterior'],
            ['code' => '6.1.27', 'name' => 'Gastos de importación', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1', 'level' => 3, 'accepts_movements' => false, 'modulo' => 'comercio_exterior'],
            ['code' => '6.1.27.01', 'name' => 'Derechos arancelarios', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1.27', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'comercio_exterior'],
            ['code' => '6.1.27.02', 'name' => 'FODINFA', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1.27', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'comercio_exterior'],
            ['code' => '6.1.27.03', 'name' => 'Gastos de agente de aduana', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1.27', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'comercio_exterior'],
            ['code' => '6.1.27.04', 'name' => 'Flete internacional importación', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1.27', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'comercio_exterior'],
            ['code' => '6.1.27.05', 'name' => 'Seguro internacional importación', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1.27', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'comercio_exterior'],
            ['code' => '6.1.27.06', 'name' => 'Gastos portuarios importación', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1.27', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'comercio_exterior'],
            ['code' => '6.1.27.07', 'name' => 'Otros gastos de importación', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1.27', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'comercio_exterior'],

            // EXPORTACIONES
            ['code' => '1.1.08', 'name' => 'Exportaciones por cobrar', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.1', 'level' => 3, 'accepts_movements' => false, 'modulo' => 'comercio_exterior'],
            ['code' => '1.1.08.01', 'name' => 'Cuentas por cobrar exterior', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.1.08', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'comercio_exterior'],
            ['code' => '1.1.08.02', 'name' => 'Documentos por cobrar exterior', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.1.08', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'comercio_exterior'],
            ['code' => '1.1.05.05', 'name' => 'Crédito tributario IVA exportaciones', 'type' => 'activo', 'nature' => 'deudora', 'parent_code' => '1.1.05', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'comercio_exterior'],
            ['code' => '4.1.05', 'name' => 'Ventas al exterior', 'type' => 'ingreso', 'nature' => 'acreedora', 'parent_code' => '4.1', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'comercio_exterior'],
            ['code' => '4.1.06', 'name' => '(-) Descuentos en ventas exterior', 'type' => 'ingreso', 'nature' => 'deudora', 'parent_code' => '4.1', 'level' => 3, 'accepts_movements' => true, 'modulo' => 'comercio_exterior'],
            ['code' => '6.1.28', 'name' => 'Gastos de exportación', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1', 'level' => 3, 'accepts_movements' => false, 'modulo' => 'comercio_exterior'],
            ['code' => '6.1.28.01', 'name' => 'Flete internacional exportación', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1.28', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'comercio_exterior'],
            ['code' => '6.1.28.02', 'name' => 'Seguro internacional exportación', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1.28', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'comercio_exterior'],
            ['code' => '6.1.28.03', 'name' => 'Gastos agente de aduana exportación', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1.28', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'comercio_exterior'],
            ['code' => '6.1.28.04', 'name' => 'Gastos portuarios exportación', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1.28', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'comercio_exterior'],
            ['code' => '6.1.28.05', 'name' => 'Certificados de origen', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1.28', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'comercio_exterior'],
            ['code' => '6.1.28.06', 'name' => 'Otros gastos de exportación', 'type' => 'gasto', 'nature' => 'deudora', 'parent_code' => '6.1.28', 'level' => 4, 'accepts_movements' => true, 'modulo' => 'comercio_exterior'],
        ];

        foreach ($accounts as $account) {
            AccountPlan::updateOrCreate(
                ['empresa_id' => null, 'code' => $account['code']],
                $account
            );
        }
    }
}
