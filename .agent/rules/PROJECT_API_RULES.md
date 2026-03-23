# Project API Rules (for AI assistants)

This document describes how the API in this repository is structured and how requests flow through the system. Follow these rules when reading, modifying, or adding API functionality.

## 1) API Entry Points (Start → End)

An API request in this project starts at the HTTP boundary and ends as a standardized JSON response.

**Start**
- Client calls an endpoint under `/api/v1/...` (versioned API).
- Routes are defined in `routes/api.php`.

**Core pipeline**
1. **Route matching** (Laravel router)
2. **Middleware chain**
3. **Controller action**
4. **Validation layer** (static validation classes)
5. **Service layer** (business logic)
6. **Repository layer** (data access via Eloquent)
7. **Model / DB** (Eloquent models + migrations)
8. **Response formatting** (standard JSON shape)

**End**
- Controller returns a JSON response using shared response helpers in `app/Traits/ApiResponser.php` (ApiResponser).

## 2) Routing Rules

### Versioning
- All API routes are grouped under `Route::prefix('v1')`.
- Do not add unversioned endpoints under `/api/...` directly.

### Route categories
- **Public routes**: No token required.
- **Protected routes**: Must be inside `Route::middleware('jwt.auth')`.
- **Admin routes**: Must be inside `Route::middleware(['jwt.auth','role:admin'])->prefix('admin')`.

### Hard rules
- Every route MUST point to an existing controller method.
- Use parameter constraints:
  - Numeric IDs: `->whereNumber('id')`
  - Slugs: `->where('slug', '[a-z0-9-]+')`

## 3) Middleware Rules (Authentication & Authorization)

### JWT authentication
- Middleware alias `jwt.auth` is registered in `bootstrap/app.php`.
- The implementation is `app/Http/Middleware/JwtAuthMiddleware.php` (JwtAuthMiddleware).
- Behavior:
  - Reads `Authorization: Bearer <token>`
  - Validates token via `JWTAuth::setToken($token)->authenticate()`
  - On success, attaches the user to the request (`$request->setUserResolver(...)`)
  - On failure, returns `401` JSON with `{ code, message }`

### Role-based authorization
- Middleware alias `role` is `app/Http/Middleware/RoleMiddleware.php` (RoleMiddleware).
- Admin endpoints MUST include `role:admin`.
- RoleMiddleware assumes the user is already attached by `jwt.auth`.

## 4) Controller Rules (HTTP layer)

Controllers live under:
- `App\Http\Controllers\Api\...` (public/protected)
- `App\Http\Controllers\Api\Admin\...` (admin)

Controller responsibilities:
- Parse input from `Request` and route params.
- Call the correct `Validation` method and return validation errors early.
- Call a `Service` method with validated data only.
- Convert `Service` results to standardized JSON responses.

Controllers MUST NOT:
- Contain database queries (no direct Eloquent calls in controllers).
- Contain business rules (put that in Services).

### Response helpers
All API controllers inherit from `app/Http/Controllers/Controller.php` (Controller), which includes `app/Traits/ApiResponser.php` (ApiResponser).

Standard response shapes:
- Success:
  - `{ "code": 200|201|..., "message": "…", "data": <payload> }`
- Error:
  - `{ "code": <status>, "message": "…", "errors": <optional> }`

Use these helpers consistently:
- `success($data, $message?, $code?)`
- `created($data, $message?)`
- `validation_error($errors, $message?)`
- `unauthorized($message?)`
- `forbidden($message?)`
- `not_found($message?)`
- `server_error($message?)`

## 5) Validation Rules (Input validation)

Validation lives in `App\Http\Validations\*Validation.php`.

Pattern:
- Static methods returning a Laravel `Validator` instance.
- Controllers call:
  - `$validator = XValidation::validateSomething($request, ...)`
  - `if ($validator->fails()) return $this->validation_error($validator->errors());`
  - `$validated = $validator->validated();`

Rules:
- Validation constraints MUST match database constraints from migrations:
  - string lengths, required/nullable, unique indexes, etc.
- Prefer `sometimes` for optional update fields.
- Use `exists:<table>,<column>` and `unique:<table>,<column>[,<ignoreId>]` as appropriate.

## 6) Service Rules (Business logic)

Services live in `App\Services\...Service.php`.

Service responsibilities:
- Implement business workflows and domain rules.
- Compose repository calls and manage transactions when needed (use `DB::transaction` / `DB` when appropriate).
- Return a consistent result structure to controllers.

Expected return structure (convention in this repo):
- On success:
  - `['status' => 200|201, 'data' => <payload>, 'message' => <optional>]`
- On failure:
  - `['status' => 4xx|5xx, 'message' => '…']`

Rules:
- Do not return raw `JsonResponse` from services (controllers own HTTP).
- Keep try/catch in services for consistent error handling (avoid leaking exceptions to controllers).
- If an operation depends on the authenticated user, controllers/middleware should provide it via:
  - `$request->user()` (after `jwt.auth`)
  - `auth('api')->id()` (only if the project guard is configured and used consistently)

## 7) Repository Rules (Data access)

Repositories live in:
- Interfaces: `App\Repositories\Interfaces\*RepositoryInterface.php`
- Implementations: `App\Repositories\Eloquent\*Repository.php`
- Base implementation: `app/Repositories/Eloquent/BaseRepository.php` (BaseRepository)

Dependency injection:
- Interface bindings are configured in `app/Providers/RepositoryServiceProvider.php`.

Rules:
- Services must depend on repository interfaces, not Eloquent models directly.
- Repositories must encapsulate query building and Eloquent access.
- Reuse BaseRepository methods (`find`, `findOneBy`, `create`, `update`, `delete`, etc.) unless there is domain-specific querying.

## 8) Model & Database Rules

- Models are in `App\Models\...`.
- Schema is defined in `database/migrations/...`.
- Validation rules should be derived from migrations (length, nullable, unique, default assumptions).
- If you add a new column in a migration, update:
  - Model `$fillable` / `$casts` / `$hidden` when needed
  - Validation rules
  - Any service/controller code that builds arrays for `create()` / `update()`

## 9) Authentication Rules (JWT)

- JWT library is configured via `config/jwt.php`.
- The `jwt.auth` middleware validates the token and attaches the user.

Rules:
- Protected endpoints must rely on `$request->user()` for the authenticated user.
- Logout must be implemented consistently with the chosen JWT strategy:
  - If using JWT blacklist, ensure token invalidation is performed by the JWT library/storage.
  - If using stateless JWT without blacklist, logout is effectively “client forgets token”.

## 10) Documentation & Comment Standards

This repository uses three “comment layers”:

### A) PHPDoc for classes/methods (required for API code)
Format:
- A short English summary line.
- A Vietnamese translation in parentheses on the next line or same block.
- For methods, include a blank line before tags, then `@param` for each parameter and `@return` for the return type.
- Do not add trailing descriptions after `@param` and `@return` (only type and variable/name).
- Keep it factual and action-oriented.

Example patterns (do not copy-paste into code blindly; match surrounding style):
- Class:
  - `/** Class X ... (Việt hóa) */`
- Method:
  - Multi-line method PHPDoc (preferred, matches current codebase style; without trailing descriptions):
    - `/**`
    - ` * Do something.`
    - ` * (Làm gì đó)`
    - ` *`
    - ` * @param Request $request`
    - ` * @return JsonResponse`
    - ` */`
  - Validation method (typical, matches SearchValidation):
    - `/**`
    - ` * Validate something.`
    - ` * (Xác thực ...)`
    - ` *`
    - ` * @param Request $request`
    - ` * @return ValidatorInstance`
    - ` */`
  - Service method (typical):
    - `/**`
    - ` * Do something.`
    - ` * (Làm gì đó)`
    - ` *`
    - ` * @param array $data`
    - ` * @return array`
    - ` */`
  - Repository method (typical):
    - `/**`
    - ` * Do something.`
    - ` * (Làm gì đó)`
    - ` *`
    - ` * @param array $filters`
    - ` * @return LengthAwarePaginator`
    - ` */`

Rules for `@param`:
- `@param` is used to list the input data passed into a method/function (each argument).
- Include one `@param` line per parameter.
- Use the real type (e.g. `Request`, `int`, `string`, `array`, `LengthAwarePaginator`).
- Match the parameter name exactly (e.g. `$request`, `$id`, `$data`).
- Do not add trailing descriptions after type and parameter name.
- For array shapes when useful, prefer describing keys in text rather than complex generics.

Rules for `@return`:
- Always include `@return` for public methods in Controllers/Services/Repositories.
- Prefer concrete types (`JsonResponse`, `array`, `Model`, `Collection`, `LengthAwarePaginator`) over `mixed` unless unavoidable.
- Do not add trailing descriptions after the return type.

### B) api-doc (apidoc) files
- API documentation lives in `api-doc/*.js` using `@api` tags.
- When adding/changing endpoints, update the matching api-doc file to keep docs accurate.

### C) Inline comments
- Use inline comments sparingly.
- Prefer extracting code into well-named methods/services rather than adding long inline explanations.

## 11) How to Add a New Endpoint (Checklist)

1. Add route in `routes/api.php` under the correct group (public/protected/admin).
2. Create/extend a controller method in the correct namespace.
3. Add a validation method in the appropriate `*Validation.php`.
4. Add or update a service method to implement business logic.
5. Use repositories for DB access; add repository methods only when needed.
6. Return JSON via ApiResponser helpers with correct HTTP status codes.
7. Update `api-doc/*.js` documentation.
8. Ensure migrations/models/validation align for any new fields.

## 12) Consistency Rules (Do not break these)

- Keep routes, controllers, and validations in sync.
- Do not introduce new response shapes; always use ApiResponser format.
- Do not bypass middleware for protected/admin endpoints.
- Prefer existing patterns (services return `status/data/message`) over ad-hoc responses.
