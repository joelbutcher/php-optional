# PHP Optional

Inspired by Java's [Optional](https://github.com/AdoptOpenJDK/openjdk-jdk11/blob/master/src/java.base/share/classes/java/util/function/Predicate.java) class, this package aims to provide a comprehensive API
for optional field values.

## Examples

You can use the `Optional` class in a variety of ways, see below for a few examples:

### Updating a user's profile

The below class indicates who this package may be used to update a users profile information. All fields are optional, because
our user may not want to update all fields.

```php
<?php

namespace App\DataObjects;

use JoelButcher\PhpOptional\Optional;
use Psl\Type;
use Webmozart\Assert\Assert;

class UpdateProfile
{
    public function __construct(
        private readonly int $id
        private readonly Optional $name
        private readonly Optional $email
        private readonly Options $bio
    ) {
        //
    }

    public static function fromFormRequest(int $id UpdateProfileRequest $request): self
    {
        return new self(
            id: $id,
            name: Optional::forNullable($request->get('name')),
            email: Optional::forNullable($request->get('email')),
            bio: Optional::forNullable($request->get('bio')),
        );
    }

    public static function fromArray(array $data): self
    {
        Assert::keyExists($post, 'id');
        Assert::positiveInteger($post['id']);


        return new self(
            id: $data['id'],
            name: Optional::forOptionalArrayKey($data, 'name', Type\non_empty_string()),
            email: Optional::forOptionalArrayKey($data, 'email', Type\non_empty_string()),
            bio: Optional::forOptionalArrayKey($data, 'bio', Type\non_empty_string()),
        );
    }

    public function handle(UserRepository $users): void
    {
        $user = $users->findOneById($this->id);

        // These callbacks are only called if the value for each optional field is present.
        $this->name->apply(fn (string $name) => $user->updateName($name));
        $this->email->apply(fn (string $email) => $user->updateEmail($email));
        $this->bio->apply(fn (string $bio) => $user->updateBio($bio));

        $users->save($user);
    }
}
```

Noticed the `\Psl\Type\non_empty_string()` call? That's an abstraction coming from `azjezz/psl`, which allows for having a type declared both at runtime and at static analysis level. We use it to parse inputs into valid values, or to produce crashes, if something is malformed.

The `azjezz/psl`, `Psl\Type` tooling gives us both type safety and runtime validation by implicitly validating our values:

```php
OptionalField::forPossiblyMissingArrayKey(
    ['foo' => 'bar'],
    'foo',
    \Psl\Type\positive_int()
); // crashes: `foo` does not contain a `positive-int`!
```
