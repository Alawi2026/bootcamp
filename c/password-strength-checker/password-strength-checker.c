#include <stdio.h>
#include <string.h>
#include <ctype.h>
#include <stdbool.h>
bool isThereUppercaseF(const char *password)
{
    for (int i = 0; i < strlen(password); i++)
    {
        if (isupper(password[i]))
        {
            return true;
        }
    }
    return false;
}

bool isThereLowercaseF(const char *password)
{
    for (int i = 0; i < strlen(password); i++)
    {
        if (islower(password[i]))
        {
            return true;
        }
    }
    return false;
}

bool isDigitF(const char *password)
{
    for (int i = 0; i < strlen(password); i++)
    {
        if (isdigit(password[i]))
        {
            return true;
        }
    }
    return false;
}

bool isSymbolF(const char *password)
{
    for (int i = 0; i < strlen(password); i++)
    {
        if (ispunct(password[i]))
        {
            return true;
        }
    }
    return false;
}

void check_password(const char *password)
{
    bool isMoreThan8Chars = strlen(password) > 8;
    bool isThereUppercase = isThereUppercaseF(password);
    bool isThereLowercase = isThereLowercaseF(password);
    bool isDigit = isDigitF(password);
    bool isSymbol = isSymbolF(password);

    int score = (int)isMoreThan8Chars + (int)isThereUppercase + (int)isThereLowercase + (int)isDigit + (int)isSymbol;
    printf("Your Score Is 5/%d\n", score);

    if (score < 5)
    {
        if (!isMoreThan8Chars)
        {
            printf("The Length Is Not Ok\n");
        }
        if (!isThereUppercase)
        {
            printf("There Is No Uppercase\n");
        }
        if (!isThereLowercase)
        {
            printf("There Is No Lowercase\n");
        }
        if (!isDigit)
        {
            printf("There Is No Digit\n");
        }
        if (!isSymbol)
        {
            printf("There Is No Symbol\n");
        }
    }
}
int main()
{
    char password[100];
    printf("Type a Password to check\n");
    if (scanf("%99s", password) == 1)
    {
        check_password(password);
    }
}
