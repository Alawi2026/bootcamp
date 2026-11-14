import socket
import ssl
import ipaddress



TIMEOUT = 3
BUFFER_SIZE = 1024


HTTP_PORTS = {80, 8000, 8008, 8080, 8888}
HTTPS_PORTS = {443, 8443}



def send_http_request(sock, target_ip):

    ip = ipaddress.ip_address(target_ip)

    if ip.version == 6:
        host = f"[{target_ip}]"
    else:
        host = target_ip

    request = (
        f"HEAD / HTTP/1.1\r\n"
        f"Host: {host}\r\n"
        f"User-Agent: Python-Banner-Grabbing-Tool\r\n"
        f"Connection: close\r\n"
        f"\r\n"
    )

    sock.sendall(request.encode("ascii"))



def extract_banner(data):


    decoded_text = data.decode("utf-8", errors="ignore")


    filtered_text = "".join(
        char for char in decoded_text
        if char.isprintable() or char in "\r\n\t"
    )


    lines = filtered_text.splitlines()

    first_line = lines[0].strip() if lines else ""

    if not first_line:
        return "No readable banner"

    result = first_line


    for line in lines:
        if line.lower().startswith("server:"):
            result += "\n    " + line.strip()
            break

    return result



def grab_banner(target_ip, port):

    try:

        with socket.create_connection(
            (target_ip, port),
            timeout=TIMEOUT
        ) as sock:

            sock.settimeout(TIMEOUT)


            if port in HTTPS_PORTS:

                context = ssl.create_default_context()
                context.check_hostname = False
                context.verify_mode = ssl.CERT_NONE

                with context.wrap_socket(
                    sock,
                    server_hostname=target_ip
                ) as secure_sock:

                    send_http_request(secure_sock, target_ip)
                    data = secure_sock.recv(BUFFER_SIZE)

            else:


                if port in HTTP_PORTS:
                    send_http_request(sock, target_ip)


                data = sock.recv(BUFFER_SIZE)

            if not data:
                return "No banner received"

            return extract_banner(data)

    except socket.timeout:
        return "Timeout: No response from service"

    except ConnectionRefusedError:
        return "Connection refused: Port may be closed"

    except ssl.SSLError:
        return "SSL/TLS connection error"

    except OSError as error:
        return f"Connection error: {error}"



def main():

    target_ip = input("Enter Target IP: ").strip()


    try:
        ipaddress.ip_address(target_ip)

    except ValueError:
        print("Invalid IP address!")
        return

    ports_input = input(
        "Enter ports (example: 21,22,80,443): "
    ).strip()

    try:

        if not ports_input:
            ports = [21, 22, 25, 80, 110, 143, 443, 8080]
        else:
            ports = sorted(set(
                int(port.strip())
                for port in ports_input.split(",")
            ))


        if any(port < 1 or port > 65535 for port in ports):
            print("Invalid port number!")
            return

    except ValueError:
        print("Please enter valid port numbers!")
        return

    print("\nStarting Banner Grabbing...")
    print("-" * 50)

    for port in ports:

        print(f"\n[*] Scanning Port {port}...")

        banner = grab_banner(target_ip, port)

        print(f"[+] Port {port}:")
        print(banner)

    print("\n" + "-" * 50)
    print("Banner Grabbing Completed!")


if __name__ == "__main__":
    main()