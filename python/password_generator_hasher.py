import secrets
import string
import hashlib

def generate_password(length):


    if length < 4:
        raise ValueError("Password length must be at least 4 characters.")


    lowercase = string.ascii_lowercase
    uppercase = string.ascii_uppercase
    digits = string.digits
    special_characters = string.punctuation

    all_characters = (
        string.ascii_letters +
        string.digits +
        string.punctuation
    )

    password = [
        secrets.choice(lowercase),
        secrets.choice(uppercase),
        secrets.choice(digits),
        secrets.choice(special_characters)
    ]

    for _ in range(length - 4):
        password.append(secrets.choice(all_characters))

    secrets.SystemRandom().shuffle(password)

    return "".join(password)


def calculate_hash(text):

    hash_object = hashlib.sha256(text.encode("utf-8"))

    return hash_object.hexdigest()


def main():

    print("=" * 45)
    print("Password Generator & SHA-256 Hasher")
    print("=" * 45)

    try:
        length = int(input("Enter password length: "))

        if length < 4:
            print("Error: Password length must be at least 4.")
            return

    except ValueError:
        print("Error: Please enter a valid number.")
        return

    password = generate_password(length)

    password_hash = calculate_hash(password)

    print("\n" + "-" * 45)
    print("GENERATED PASSWORD")
    print("-" * 45)

    print("Password:", password)

    print("\n" + "-" * 45)
    print("SHA-256 HASH")
    print("-" * 45)

    print("Hash:", password_hash)

    print("\n" + "=" * 45)
    print("Operation Completed Successfully!")
    print("=" * 45)


if __name__ == "__main__":
    main()