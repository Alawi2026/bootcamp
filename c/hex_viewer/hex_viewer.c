#include <stdio.h>

int main(int argc, char *argv[])
{
    FILE *file;
    unsigned char buffer[16];
    size_t BytesRead;
    unsigned long offset = 0;

    if (argc != 2)
    {
        printf("Enter the file name Please.\n");
        return 1;
    }

    file = fopen(argv[1], "rb");

    if (file == NULL)
    {
        printf("Erorr: Cannot open the file.\n");
        return 1;
    }

    printf("------------------------------------------------------------------------------\n");
    printf("ofset      |Hexadecimal Values                  \t\t|ASCII\n");
    printf("--------------------------------------------------------------------------------\n");

    while ((BytesRead = fread(buffer, 1, 16, file)) > 0)
    {
        printf("%08lX   |", offset);

        for (size_t i = 0; i < BytesRead; i++)
        {
            printf("%02X ", buffer[i]);
        }

        for (size_t i = BytesRead; i < 16; i++)
        {
            printf("   ");
        }

        printf("   | ");

        for (size_t i = 0; i < BytesRead; i++)
        {
            if (buffer[i] >= 32 && buffer[i] <= 126)
            {
                printf("%c", buffer[i]);
            }
            else
            {
                printf(".");
            }
        }

        printf("\n");

        offset += BytesRead;
    }
    printf("--------------------------------------------------------------------------------\n");
    fclose(file);

    printf("\n\n----------------------------------------------\n");
    printf("File reading completed successfully.\n");
    printf("----------------------------------------------");

    return 0;
}