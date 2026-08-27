# Changelog

## [0.15.0](https://github.com/getmilpa/live/compare/v0.14.0...v0.15.0) (2026-08-27)


### Features

* **support:** a Clock primitive (SystemClock/FixedClock) and a deterministic `stamp` effect ([#30](https://github.com/getmilpa/live/issues/30)) ([6ccc138](https://github.com/getmilpa/live/commit/6ccc138e904532c7d0c682c2985042b67d3cfb0a))

## [0.14.0](https://github.com/getmilpa/live/compare/v0.13.0...v0.14.0) (2026-08-27)


### Features

* **components:** a state can declare onExit effects — the full lifecycle in order ([#28](https://github.com/getmilpa/live/issues/28)) ([79243fc](https://github.com/getmilpa/live/commit/79243fcf388f5832b590907dc8a10678663e137a))

## [0.13.0](https://github.com/getmilpa/live/compare/v0.12.0...v0.13.0) (2026-08-27)


### Features

* **components:** a state can declare onEnter effects (v1 state-enter entry actions) ([#26](https://github.com/getmilpa/live/issues/26)) ([3eda9fc](https://github.com/getmilpa/live/commit/3eda9fc9a272598c6db80c49fa4c68c80009c94d))

## [0.12.0](https://github.com/getmilpa/live/compare/v0.11.0...v0.12.0) (2026-08-27)


### Features

* **components:** guard leaves can read v1 Refs (dot-path) and compare field-to-field via valueRef ([#24](https://github.com/getmilpa/live/issues/24)) ([aef2517](https://github.com/getmilpa/live/commit/aef25172b99ecedeb017c7de635c466ecbf643e8))

## [0.11.0](https://github.com/getmilpa/live/compare/v0.10.0...v0.11.0) (2026-08-27)


### Features

* **components:** compound transition guards (all/any/not), still data not code ([#22](https://github.com/getmilpa/live/issues/22)) ([c658da3](https://github.com/getmilpa/live/commit/c658da327436084f5ad1ca2cafa72c8726f656e0))

## [0.10.0](https://github.com/getmilpa/live/compare/v0.9.0...v0.10.0) (2026-08-27)


### Features

* **components:** a StateMachine transition can carry a declared guard (v1 Condition) ([#20](https://github.com/getmilpa/live/issues/20)) ([68c3133](https://github.com/getmilpa/live/commit/68c3133902114954e09dc386cc70973d9832151b))

## [0.9.0](https://github.com/getmilpa/live/compare/v0.8.0...v0.9.0) (2026-08-27)


### Features

* **components:** StateMachine's `fire` action is scoped per-event (scopeBy: event) ([#18](https://github.com/getmilpa/live/issues/18)) ([c534573](https://github.com/getmilpa/live/commit/c534573b1d60b1b3981fb1eb1b02f4938c709053))

## [0.8.0](https://github.com/getmilpa/live/compare/v0.7.0...v0.8.0) (2026-08-27)


### Features

* **components:** a StateMachine can be defined by props, not just baked into the class ([#16](https://github.com/getmilpa/live/issues/16)) ([ee8eee5](https://github.com/getmilpa/live/commit/ee8eee5001f366a813ebbd89533b9d6fbe11a85d))

## [0.7.0](https://github.com/getmilpa/live/compare/v0.6.0...v0.7.0) (2026-08-27)


### Features

* **components:** StateMachine transitions fire allow-listed effects (data, not code) ([#14](https://github.com/getmilpa/live/issues/14)) ([abf3640](https://github.com/getmilpa/live/commit/abf36403111285a4eec0f204ac128f4bbd686860))

## [0.6.0](https://github.com/getmilpa/live/compare/v0.5.1...v0.6.0) (2026-08-27)


### Features

* **components:** a closed declarative StateMachine component over the live wire ([#12](https://github.com/getmilpa/live/issues/12)) ([50b79db](https://github.com/getmilpa/live/commit/50b79db522931232bddbac5d29a7c65beacc775d))

## [0.4.1](https://github.com/getmilpa/live/compare/v0.4.0...v0.4.1) (2026-08-01)


### Bug Fixes

* **deps:** el pin de milpa/core acepta la linea 0.7 ([ffab11c](https://github.com/getmilpa/live/commit/ffab11cc5e0870262860e76c13b3db64c882d8b4))

## [0.4.0](https://github.com/getmilpa/live/compare/v0.3.0...v0.4.0) (2026-07-28)


### Features

* the falsifier for a candidate law, written before its verdict ([f826ca9](https://github.com/getmilpa/live/commit/f826ca99189071c38d4404503f616f8fb5668d5d))

## [0.3.0](https://github.com/getmilpa/live/compare/v0.2.0...v0.3.0) (2026-07-28)


### Features

* ship the conformance every surface renderer must answer ([d92b4b8](https://github.com/getmilpa/live/commit/d92b4b8707186dc91cde2130f9f374a93622d1c3))

## [0.2.0](https://github.com/getmilpa/live/compare/v0.1.1...v0.2.0) (2026-07-28)


### Features

* publicar Milpa\Live\Schema — de esquema JSON a formulario tipado ([ae400d3](https://github.com/getmilpa/live/commit/ae400d32cd479abcd7c031cb5c1db51a683129fc))

## [0.1.1](https://github.com/getmilpa/live/compare/v0.1.0...v0.1.1) (2026-07-12)


### Bug Fixes

* receive milpa/core 0.6 — pin bump ([1ffc933](https://github.com/getmilpa/live/commit/1ffc93394e7d8f73d7a66e840f04c72f87c6285f))

## 0.1.0 (2026-07-08)


### Features

* milpa/live initial public release ([96e4c6e](https://github.com/getmilpa/live/commit/96e4c6e8b8d15555331941ba8520b9ce0132339f))


### Miscellaneous Chores

* release 0.1.0 ([0c3fbbd](https://github.com/getmilpa/live/commit/0c3fbbdf6c0d1132f5aa978a3c2963121b46bc11))
