#include <stdio.h>
#include <string.h>
#include <stdbool.h>
#include <ctype.h>

int main()
{
    char password[100];

    while (true)
    {
        printf("\nEnter password: ");
        fgets(password, sizeof(password), stdin);

        password[strcspn(password, "\n")] = 0;

        bool isMoreThan8Chars = strlen(password) > 8;
        bool isThereUppercase = false;
        bool isThereLowercase = false;
        bool isDigit = false;
        bool isSymbol = false;

        // 2. الفحص داخل النص
        for (int i = 0; password[i] != '\0'; i++)
        {
            if (isupper(password[i]))
                isThereUppercase = true;
            else if (islower(password[i]))
                isThereLowercase = true;
            else if (isdigit(password[i]))
                isDigit = true;
            else
                isSymbol = true;
        }

        int score = isMoreThan8Chars + isThereUppercase + isThereLowercase + isDigit + isSymbol;

        if (score == 5)
        {
            printf("Strong Password! Score: 5/5\n");
            break;
        }

        printf("Score: %d/5\nMissing criteria:\n", score);
        if (!isMoreThan8Chars)
            printf("- Must be more than 8 characters\n");
        if (!isThereUppercase)
            printf("- Add uppercase letters (A-Z)\n");
        if (!isThereLowercase)
            printf("- Add lowercase letters (a-z)\n");
        if (!isDigit)
            printf("- Add digits (0-9)\n");
        if (!isSymbol)
            printf("- Add symbols (!@#$%%...)\n");
    }

    return 0;
}