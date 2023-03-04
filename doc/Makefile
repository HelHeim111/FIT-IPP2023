FILE_NAME = readme1

all:
	latex $(FILE_NAME).tex
	latex $(FILE_NAME).tex
	dvips $(FILE_NAME).dvi
	ps2pdf $(FILE_NAME).ps

clean:
	rm -f $(FILE_NAME).{out,log,aux,pdf,ps,dvi}
