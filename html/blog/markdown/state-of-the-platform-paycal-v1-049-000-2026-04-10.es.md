---
title: Estado de la Plataforma: PayCal Versión 1.049.000
date: 2026-04-10
author: Equipo de PayCal
tags: release, accessibility, privacy, security, premium
---

## Resumen

PayCal Versión 1.049.000 marca un hito arquitectónico importante. La plataforma ahora opera como un entorno deny-safe para el seguimiento profesional del trabajo, con soberanía de privacidad y accesibilidad radical integradas en el comportamiento central del producto.

Con una base de código de 945 archivos matemáticamente verificados, esta versión representa el paso de una expansión rápida de funciones a una estabilidad de plataforma duradera.

## La accesibilidad ahora es verificable

Al 10 de abril de 2026, la WCAG Theme Contrast Matrix confirma una tasa de aprobación total en todo el sistema visual.

◆ 68 temas escaneados en 2,040 puntos de control
◆ Umbral mínimo de contraste de 4.75:1 aplicado en todos los tokens de tema
◆ Cobertura de todos los diseños seleccionables, incluyendo Matrix (15.56:1) y Akira (14.02:1)

El resultado es una legibilidad consistente, sin importar la preferencia de tema.

## Soberanía de privacidad: tres pilares de seguridad

### 1) Autenticación solo con Passkeys (Workstream G)

PayCal completó la eliminación del puente de credenciales del navegador y ahora opera únicamente con passkeys.

◆ Sin riesgo de exposición de bases de datos de contraseñas
◆ WebAuthn + HKDF derivan localmente una Key Encryption Key (KEK)
◆ El servidor solo recibe material de clave envuelto

### 2) Limpieza automática de datos (Workstream D)

El estado sensible se mantiene de vida corta de forma estricta.

◆ Ocultar la pestaña o salir de la página activa un DOM Sensitivity Scrub
◆ Las claves de seguridad y el estado sensible se borran de memoria
◆ La retención de datos sigue límites estrictos de necesidad

### 3) Telemetría Privacy Guard (Workstream B)

Se mantiene la observabilidad operativa sin filtrar identidad.

◆ Telemetría anonimizada
◆ Entrega por lotes con jitter aleatorio
◆ Los registros evitan correlación con sesiones o eventos de ingresos

## Herramientas profesionales destacadas

### AriaEcho Narration

La narración orientada a accesibilidad transforma registros de tiempo y pago en lenguaje natural y profesional para flujos asistivos.

### Private Math (motor fiscal local)

Los cálculos de impuestos se ejecutan completamente en el navegador, manteniendo los cálculos sensibles de ingresos fuera de servidores remotos.

### Exportaciones profesionales

Exportaciones en PDF, CSV y texto disponibles con un clic. Export Identity Inversion usa una identidad temporal saneada para encabezados y la elimina inmediatamente después de la descarga.

### Safety Net Recovery

Orphaned Work Recovery detecta registros no vinculados tras eliminaciones de sitios y facilita su reconexión para preservar continuidad histórica.

## Nivel Premium: colaboración sin compromisos

Las funciones premium para organizaciones ofrecen mayor control operativo sin sacrificar la privacidad individual.

◆ Organization Hub para flujos de empleadores y equipos
◆ Modelo refinado de alcance de roles con permisos granulares
◆ Vistas de calendario delegadas para supervisión de gestión
◆ DEK Auto-Bootstrap para preparación de cifrado inmediata al visitar la página

## Cierre

PayCal v1.049.000 es más que un incremento de versión. Es un compromiso de plataforma con diseño accesible, soberanía de privacidad y manejo de datos controlado por el usuario a escala.

Seguro. Accesible. Tuyo. Esto es PayCal.
