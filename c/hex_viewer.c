#include <stdio.h>

int main(int arg_count, char *arg_values[])
{
    FILE *fp;
    unsigned char data_chunk[16];
    size_t fetched;
    unsigned long file_offset = 0;


    const char *target_file = (arg_count < 2) ? "alawi.txt" : arg_values[1];

    fp = fopen(target_file, "rb");

    if (fp == NULL)
    {
        printf("Error: Failed to open target file '%s'.\n", target_file);
        return 1;
    }

    printf("Reading file: %s\n\n", target_file);
    printf("Offset   Hexadecimal Values                          ASCII\n");
    printf("------------------------------------------------------------\n");

    while ((fetched = fread(data_chunk, 1, 16, fp)) > 0)
    {
        printf("%08lX  ", file_offset);

        for (size_t idx = 0; idx < fetched; idx++)
        {
            printf("%02X ", data_chunk[idx]);
        }

        for (size_t idx = fetched; idx < 16; idx++)
        {
            printf("   ");
        }

        printf(" ");

        for (size_t idx = 0; idx < fetched; idx++)
        {
            if (data_chunk[idx] >= 32 && data_chunk[idx] <= 126)
            {
                printf("%c", data_chunk[idx]);
            }
            else
            {
                printf(".");
            }
        }

        printf("\n");

        file_offset += fetched;
    }

    fclose(fp);

    printf("\nFile reading completed successfully.\n");

    return 0;
}